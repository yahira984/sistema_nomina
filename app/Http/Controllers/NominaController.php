<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Asistencia;
use App\Models\Nomina;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteSemanalExport;

class NominaController extends Controller
{
    public function index(Request $request)
    {
        $hoy = Carbon::now();
        // Referencia Maestra LUGARTH: La semana "global" cierra en Miércoles
        $miercolesAutomatico = $hoy->isWednesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::WEDNESDAY)->endOfDay();
        
        $fechaCorteStr = $request->input('fecha_corte', $miercolesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay(); 
        $semanaActual = $inicioSemana->weekOfYear;

        $semanasDisponibles = [];
        $iterador = $miercolesAutomatico->copy();
        for ($i = 0; $i < 10; $i++) {
            $inicio = $iterador->copy()->subDays(6);
            $semanasDisponibles[] = [
                'fecha_corte' => $iterador->format('Y-m-d'),
                'etiqueta'    => 'Sem. ' . $inicio->weekOfYear . ' (' . $inicio->locale('es')->isoFormat('D MMM') . ' al ' . $iterador->locale('es')->isoFormat('D MMM') . ')'
            ];
            $iterador->subWeek();
        }

        $empleados = Empleado::where('estatus', true)->get()->map(function ($empleado) use ($semanaActual) {
            $nomina = Nomina::where('empleado_id', $empleado->id)->where('numero_semana', $semanaActual)->first();
            $empleado->nomina_generada = $nomina ? true : false;
            $empleado->nomina_id = $nomina ? $nomina->id : null;
            $empleado->pagado = $nomina ? $nomina->pagado : false;
            return $empleado;
        });

        $historial = Nomina::with('empleado')->orderBy('numero_semana', 'desc')->orderBy('id', 'desc')->get();

        return Inertia::render('Nominas/Index', [
            'empleados' => $empleados,
            'historial' => $historial,
            'semanaActual' => $semanaActual,
            'semanasDisponibles' => $semanasDisponibles,
            'fechaCorteActual' => $fechaCorteStr
        ]);
    }

    public function generarRecibo(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        $hoy = Carbon::now();
        $miercolesAutomatico = $hoy->isWednesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::WEDNESDAY)->endOfDay();
        
        $fechaCorteMiercoles = $request->input('fecha_corte', $miercolesAutomatico->format('Y-m-d'));
        
        // Obtenemos la semana en base a la fecha global
        $numero_semana = Carbon::parse($fechaCorteMiercoles)->copy()->subDays(6)->startOfDay()->weekOfYear;

        $datosPDF = $this->procesarCalculosNomina($empleado->id, $numero_semana, $fechaCorteMiercoles);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $datosPDF);
        return $pdf->stream('Recibo_Semana_'.$numero_semana.'_'.$empleado->nombre_completo.'.pdf');
    }

    public function descargar(Nomina $nomina)
    {
        $nomina->load('empleado');
        $es_estudiante = $nomina->empleado->sueldo_por_hora > 0;
        
        if ($es_estudiante) {
            $fechaCorteMiercoles = Carbon::parse($nomina->fecha_fin)->addDay()->format('Y-m-d');
        } else {
            $fechaCorteMiercoles = Carbon::parse($nomina->fecha_fin)->format('Y-m-d');
        }
        
        $datosPDF = $this->procesarCalculosNomina($nomina->empleado_id, $nomina->numero_semana, $fechaCorteMiercoles);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $datosPDF);
        return $pdf->stream('Recibo_Semana_'.$nomina->numero_semana.'_'.$nomina->empleado->nombre_completo.'.pdf');
    }

    public function pagar(Nomina $nomina)
    {
        $empleado = $nomina->empleado;
        if (!$nomina->pagado) {
            $nomina->update(['pagado' => true]);
            if ($empleado->saldo_prestamo > 0) {
                $descuento_aplicado = min($empleado->saldo_prestamo, $empleado->cuota_prestamo);
                $empleado->decrement('saldo_prestamo', $descuento_aplicado);
            }
        } else {
            $nomina->update(['pagado' => false]);
            if ($empleado->cuota_prestamo > 0) {
                $empleado->increment('saldo_prestamo', $empleado->cuota_prestamo);
            }
        }
        return back();
    }

    public function reporteGlobal($semana)
    {
        return Excel::download(new ReporteSemanalExport($semana), 'Resumen_Semana_'.$semana.'.xlsx');
    }

    // --- CEREBRO CON CAMBIO DE LÍNEA TEMPORAL ---
    private function procesarCalculosNomina($empleado_id, $numero_semana, $fechaCorteMiercoles)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        
        $finNormal = Carbon::parse($fechaCorteMiercoles)->endOfDay(); 
        $inicioNormal = $finNormal->copy()->subDays(6)->startOfDay(); 

        $es_estudiante = $empleado->sueldo_por_hora > 0;

        // ⏱️ SALTO EN EL TIEMPO PARA ESTUDIANTES
        if ($es_estudiante) {
            $finReal = $finNormal->copy()->subDay()->endOfDay(); 
            $inicioReal = $finReal->copy()->subDays(6)->startOfDay(); 
        } else {
            $finReal = $finNormal; 
            $inicioReal = $inicioNormal; 
        }

        $asistenciasSemana = Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [$inicioReal, $finReal])->get();

        $total_horas_extra = $asistenciasSemana->filter(function($asis) use ($finReal) {
            return $asis->fecha !== $finReal->format('Y-m-d');
        })->sum('horas_extra');

        $diaPasado = $inicioReal->copy()->subDay();
        $asistenciaDiaPasado = Asistencia::where('empleado_id', $empleado->id)
            ->whereDate('fecha', $diaPasado->format('Y-m-d'))
            ->first();

        if ($asistenciaDiaPasado) {
            $total_horas_extra += $asistenciaDiaPasado->horas_extra;
        }

        $minutos_tarde_acumulados = $asistenciasSemana->sum('minutos_tarde');
        $dias_falta = $asistenciasSemana->where('tipo_asistencia', 'Falta')->count();
        $dias_incapacidad = $asistenciasSemana->where('tipo_asistencia', 'Incapacidad')->count();
        $dias_vacaciones = $asistenciasSemana->where('tipo_asistencia', 'Vacaciones')->count();

        if ($es_estudiante) {
            // LÓGICA ESTUDIANTE (Inmunidad a faltas y retardos)
            $sueldo_por_hora = $empleado->sueldo_por_hora;
            $sueldo_diario = $sueldo_por_hora * 8; 
            
            $total_horas_normales = $asistenciasSemana->sum('horas_trabajadas');
            $sueldo_base = $total_horas_normales * $sueldo_por_hora;
            
            $descuento_faltas = 0; 
            $descuento_retardos = 0; // <- INMUNIDAD ACTIVADA A RETARDOS
        } else {
            // LÓGICA PLANTA (Se les castiga el retardo y la falta)
            $sueldo_semanal = $empleado->sueldo_semanal;
            $sueldo_diario = $sueldo_semanal > 0 ? $sueldo_semanal / 7 : 0;
            $sueldo_por_hora = $sueldo_diario > 0 ? $sueldo_diario / 8 : 0;
            $costo_por_minuto = $sueldo_por_hora > 0 ? $sueldo_por_hora / 60 : 0;

            $sueldo_base = $sueldo_semanal; 
            $total_horas_normales = 48; 
            
            $descuento_faltas = $dias_falta * ($sueldo_diario * 1.1875);
            $descuento_retardos = $minutos_tarde_acumulados * $costo_por_minuto; // <- EL CASTIGO DE PLANTA
        }
        
        $pago_extra = $total_horas_extra * ($sueldo_por_hora * 2); 
        $pago_incapacidad = $dias_incapacidad * ($sueldo_diario * 0.60); 
        $pago_vacaciones = $dias_vacaciones * ($sueldo_diario * 1.25); 
        
        $total_percepciones = $sueldo_base + $pago_extra + $pago_incapacidad + $pago_vacaciones;

        $descuento_prestamo = 0;
        if ($empleado->saldo_prestamo > 0) {
            $descuento_prestamo = min($empleado->saldo_prestamo, $empleado->cuota_prestamo);
        }

        $deducciones_ley = $empleado->descuento_imss + $empleado->descuento_isr + $empleado->descuento_infonavit;
        $total_deducciones = $descuento_retardos + $descuento_faltas + $descuento_prestamo + $deducciones_ley;
        
        $pago_neto = $total_percepciones - $total_deducciones;

        $nomina = Nomina::updateOrCreate(
            ['empleado_id' => $empleado->id, 'numero_semana' => $numero_semana],
            [
                'fecha_inicio' => $inicioReal->format('Y-m-d'), 
                'fecha_fin' => $finReal->format('Y-m-d'),
                'horas_normales' => $total_horas_normales,
                'horas_extra' => $total_horas_extra,
                'total_percepciones' => $total_percepciones,
                'total_deducciones' => $total_deducciones,
                'pago_neto' => $pago_neto,
            ]
        );

        return [
            'nomina' => $nomina,
            'dias_falta' => $dias_falta,
            'dias_incapacidad' => $dias_incapacidad,
            'dias_vacaciones' => $dias_vacaciones,
            'pago_incapacidad' => $pago_incapacidad,
            'pago_vacaciones' => $pago_vacaciones,
            'pago_normal' => $sueldo_base,
            'pago_extra' => $pago_extra,
            'minutos_tarde_acumulados' => $minutos_tarde_acumulados,
            'descuento_retardos' => $descuento_retardos,
            'descuento_faltas' => $descuento_faltas,
            'total_percepciones' => $total_percepciones,
            'total_deducciones' => $total_deducciones,
            'pago_neto' => $pago_neto
        ];
    }
}