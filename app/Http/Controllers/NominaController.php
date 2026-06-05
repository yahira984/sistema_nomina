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
        
        $fechaCorteStr = $request->input('fecha_corte', $miercolesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay();

        $datosPDF = $this->procesarCalculosNomina($empleado->id, $inicioSemana->weekOfYear, $inicioSemana, $finSemana);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $datosPDF);
        return $pdf->stream('Recibo_Semana_'.$inicioSemana->weekOfYear.'_'.$empleado->nombre_completo.'.pdf');
    }

    public function descargar(Nomina $nomina)
    {
        $nomina->load('empleado');
        $inicioSemana = Carbon::parse($nomina->fecha_inicio);
        $finSemana = Carbon::parse($nomina->fecha_fin);
        
        $datosPDF = $this->procesarCalculosNomina($nomina->empleado_id, $nomina->numero_semana, $inicioSemana, $finSemana);

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

    // --- EL CEREBRO FINAL: ESTUDIANTES VS PLANTA ---
    private function procesarCalculosNomina($empleado_id, $numero_semana, $inicioSemana, $finSemana)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        
        $asistenciasSemana = Asistencia::where('empleado_id', $empleado->id)->whereBetween('fecha', [$inicioSemana, $finSemana])->get();

        $total_horas_extra = $asistenciasSemana->filter(function($asis) use ($finSemana) {
            return $asis->fecha !== $finSemana->format('Y-m-d');
        })->sum('horas_extra');

        $miercolesPasado = $inicioSemana->copy()->subDay();
        $asistenciaMiercolesPasado = Asistencia::where('empleado_id', $empleado->id)
            ->whereDate('fecha', $miercolesPasado->format('Y-m-d'))
            ->first();

        if ($asistenciaMiercolesPasado) {
            $total_horas_extra += $asistenciaMiercolesPasado->horas_extra;
        }

        $minutos_tarde_acumulados = $asistenciasSemana->sum('minutos_tarde');
        $dias_falta = $asistenciasSemana->where('tipo_asistencia', 'Falta')->count();
        $dias_incapacidad = $asistenciasSemana->where('tipo_asistencia', 'Incapacidad')->count();
        $dias_vacaciones = $asistenciasSemana->where('tipo_asistencia', 'Vacaciones')->count();

        // MAGIA: ¿Es estudiante o es de planta?
        $es_estudiante = $empleado->sueldo_por_hora > 0;

        if ($es_estudiante) {
            // LÓGICA ESTUDIANTE (Multiplica sus horas directas)
            $sueldo_por_hora = $empleado->sueldo_por_hora;
            $sueldo_diario = $sueldo_por_hora * 8; 
            $costo_por_minuto = $sueldo_por_hora / 60;
            
            $total_horas_normales = $asistenciasSemana->sum('horas_trabajadas');
            $sueldo_base = $total_horas_normales * $sueldo_por_hora; // SU PAGO REAL
            $descuento_faltas = 0; // A los estudiantes no se les cobra la falta
        } else {
            // LÓGICA PLANTA (Su semana completa)
            $sueldo_semanal = $empleado->sueldo_semanal;
            $sueldo_diario = $sueldo_semanal > 0 ? $sueldo_semanal / 7 : 0;
            $sueldo_por_hora = $sueldo_diario > 0 ? $sueldo_diario / 8 : 0;
            $costo_por_minuto = $sueldo_por_hora > 0 ? $sueldo_por_hora / 60 : 0;

            $sueldo_base = $sueldo_semanal; // BASE COMPLETA
            $descuento_faltas = $dias_falta * ($sueldo_diario * 1.1875);
            $total_horas_normales = 48; 
        }
        
        $pago_extra = $total_horas_extra * ($sueldo_por_hora * 2); 
        $pago_incapacidad = $dias_incapacidad * ($sueldo_diario * 0.60); 
        $pago_vacaciones = $dias_vacaciones * ($sueldo_diario * 1.25); 
        
        $total_percepciones = $sueldo_base + $pago_extra + $pago_incapacidad + $pago_vacaciones;

        $descuento_retardos = $minutos_tarde_acumulados * $costo_por_minuto; 

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
                'fecha_inicio' => $inicioSemana->format('Y-m-d'),
                'fecha_fin' => $finSemana->format('Y-m-d'),
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
            'pago_normal' => $sueldo_base, // AHORA SÍ PASAMOS EL DINERO
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