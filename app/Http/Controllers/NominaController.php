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
        $martesAutomatico = $hoy->isTuesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();
        
        $fechaCorteStr = $request->input('fecha_corte', $martesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay();
        $semanaActual = $inicioSemana->weekOfYear;

        $semanasDisponibles = [];
        $iterador = $martesAutomatico->copy();
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
        $martesAutomatico = $hoy->isTuesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();
        
        $fechaCorteStr = $request->input('fecha_corte', $martesAutomatico->format('Y-m-d'));
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
            // Si lo estamos marcando como PAGADO apenas...
            $nomina->update(['pagado' => true]);

            // Le descontamos de su deuda total lo que le quitamos en esta nómina
            // Ojo: Para saber cuánto le quitamos exactamente, calculamos qué pasó:
            $descuento_aplicado = 0;
            if ($empleado->saldo_prestamo > 0) {
                $descuento_aplicado = min($empleado->saldo_prestamo, $empleado->cuota_prestamo);
                $empleado->decrement('saldo_prestamo', $descuento_aplicado);
            }
        } else {
            // Si por error se arrepienten y lo marcan como PENDIENTE otra vez...
            $nomina->update(['pagado' => false]);

            // Le devolvemos ese saldo a su deuda para que no pierda dinero la empresa
            $descuento_aplicado = min($empleado->saldo_prestamo + $empleado->cuota_prestamo, $empleado->cuota_prestamo);
            // Solo devolvemos si realmente tenía cuota registrada
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

    // --- EL CEREBRO MATEMÁTICO CENTRAL CORREGIDO ---
    private function procesarCalculosNomina($empleado_id, $numero_semana, $inicioSemana, $finSemana)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        $asistencias = Asistencia::where('empleado_id', $empleado->id)->whereBetween('fecha', [$inicioSemana, $finSemana])->get();

        $total_horas_normales = $asistencias->sum('horas_trabajadas');
        $total_horas_extra = $asistencias->sum('horas_extra');
        $minutos_tarde_acumulados = $asistencias->sum('minutos_tarde');
        
        $dias_falta = $asistencias->where('tipo_asistencia', 'Falta')->count();
        $dias_incapacidad = $asistencias->where('tipo_asistencia', 'Incapacidad')->count();

        $pago_normal = $total_horas_normales * $empleado->sueldo_por_hora;
        $pago_extra = $total_extra = $total_horas_extra * ($empleado->sueldo_por_hora * 2); 
        $pago_incapacidad = $dias_incapacidad * 8 * ($empleado->sueldo_por_hora * 0.60); 
        
        $total_percepciones = $pago_normal + $pago_extra + $pago_incapacidad;

        $bloques_retardo = floor($minutos_tarde_acumulados / 30);
        $descuento_retardos = $bloques_retardo * ($empleado->sueldo_por_hora * 0.5); 

        // --- LÓGICA INTELIGENTE DE PRÉSTAMOS ---
        $descuento_prestamo = 0;
        if ($empleado->saldo_prestamo > 0) {
            // Si lo que debe es menor a su cuota normal, solo le cobramos lo que le falta
            if ($empleado->saldo_prestamo < $empleado->cuota_prestamo) {
                $descuento_prestamo = $empleado->saldo_prestamo;
            } else {
                $descuento_prestamo = $empleado->cuota_prestamo;
            }
        }

        $total_deducciones = $descuento_retardos + $descuento_prestamo + $empleado->cuota_seguro;
        
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
            'pago_incapacidad' => $pago_incapacidad,
            'pago_normal' => $pago_normal,
            'pago_extra' => $pago_extra,
            'minutos_tarde_acumulados' => $minutos_tarde_acumulados,
            'descuento_retardos' => $descuento_retardos,
            'total_percepciones' => $total_percepciones,
            'total_deducciones' => $total_deducciones,
            'pago_neto' => $pago_neto
        ];
    }
}