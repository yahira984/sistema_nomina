<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Asistencia;
use App\Models\Nomina;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class NominaController extends Controller
{
    public function index(Request $request)
    {
        $hoy = Carbon::now();
        
        // 1. EL CORTE AUTOMÁTICO: Buscamos el martes más reciente
        $martesAutomatico = $hoy->isTuesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();
        
        // 2. EL CORTE MANUAL: Si la contadora seleccionó una fecha en el sistema, la usamos. Si no, usamos la automática.
        $fechaCorteStr = $request->input('fecha_corte', $martesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay();
        $semanaActual = $inicioSemana->weekOfYear;

        // 3. GENERAR EL MENÚ DESPLEGABLE: Creamos las últimas 10 semanas para que pueda viajar en el tiempo
        $semanasDisponibles = [];
        $iterador = $martesAutomatico->copy();
        for ($i = 0; $i < 10; $i++) {
            $inicio = $iterador->copy()->subDays(6);
            $semanasDisponibles[] = [
                'fecha_corte' => $iterador->format('Y-m-d'),
                'etiqueta'    => 'Sem. ' . $inicio->weekOfYear . ' (' . $inicio->locale('es')->isoFormat('D MMM') . ' al ' . $iterador->locale('es')->isoFormat('D MMM') . ')'
            ];
            $iterador->subWeek(); // Retrocedemos una semana para el siguiente ciclo
        }

        // Revisar el estatus de los empleados pero SOLO de la semana que seleccionó
        $empleados = Empleado::where('estatus', true)->get()->map(function ($empleado) use ($semanaActual) {
            $nomina = Nomina::where('empleado_id', $empleado->id)
                            ->where('numero_semana', $semanaActual)
                            ->first();

            $empleado->nomina_generada = $nomina ? true : false;
            $empleado->nomina_id = $nomina ? $nomina->id : null;
            $empleado->pagado = $nomina ? $nomina->pagado : false;
            
            return $empleado;
        });

        $historial = Nomina::with('empleado')
                           ->orderBy('numero_semana', 'desc')
                           ->orderBy('id', 'desc')
                           ->get();

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
        
        // Recibimos la fecha exacta que la contadora quiere calcular (por defecto el martes pasado)
        $fechaCorteStr = $request->input('fecha_corte', $martesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay();
        $numero_semana = $inicioSemana->weekOfYear;

        $asistencias = Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->get();

        $horas_normales = 0;
        $horas_extra = 0;

        foreach($asistencias as $asistencia) {
            $fecha_asistencia = Carbon::parse($asistencia->fecha);
            if ($fecha_asistencia->isSaturday()) {
                $horas_extra += $asistencia->horas_trabajadas;
            } else {
                $horas_normales += $asistencia->horas_trabajadas;
            }
        }

        $pago_normal = $horas_normales * $empleado->sueldo_por_hora;
        $pago_extra = $horas_extra * ($empleado->sueldo_por_hora * 2); 
        $total_percepciones = $pago_normal + $pago_extra;
        $total_deducciones = 0; 
        $pago_neto = $total_percepciones - $total_deducciones;

        $nomina = Nomina::updateOrCreate(
            ['empleado_id' => $empleado->id, 'numero_semana' => $numero_semana],
            [
                'fecha_inicio' => $inicioSemana->format('Y-m-d'),
                'fecha_fin' => $finSemana->format('Y-m-d'),
                'horas_normales' => $horas_normales,
                'horas_extra' => $horas_extra,
                'total_percepciones' => $total_percepciones,
                'total_deducciones' => $total_deducciones,
                'pago_neto' => $pago_neto,
            ]
        );

        $pdf = Pdf::loadView('pdf.recibo', compact('nomina', 'empleado'));
        return $pdf->stream('Recibo_Semana_'.$numero_semana.'_'.$empleado->nombre_completo.'.pdf');
    }

    public function descargar(Nomina $nomina)
    {
        $empleado = $nomina->empleado;
        $pdf = Pdf::loadView('pdf.recibo', compact('nomina', 'empleado'));
        return $pdf->stream('Recibo_Semana_'.$nomina->numero_semana.'_'.$empleado->nombre_completo.'.pdf');
    }

    public function pagar(Nomina $nomina)
    {
        $nomina->update(['pagado' => !$nomina->pagado]);
        return back();
    }
}