<?php

namespace App\Http\Controllers;

use App\Exports\ReciboIndividualExport;
use App\Exports\ReporteSemanalExport;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class NominaController extends Controller
{
    private const DIAS_LABORALES_SEMANA = 6;
    private const HORAS_BASE_SEMANA = 48;

    public function index(Request $request)
    {
        $hoy = Carbon::now();

        $martesAutomatico = $hoy->isTuesday()
            ? $hoy->copy()->endOfDay()
            : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();

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
                'numero_semana' => $inicio->weekOfYear,
                'etiqueta' => 'Sem. ' . $inicio->weekOfYear . ' (' . $inicio->locale('es')->isoFormat('D MMM YYYY') . ' al ' . $iterador->locale('es')->isoFormat('D MMM YYYY') . ')',
            ];
            $iterador->subWeek();
        }

        $empleados = Empleado::where('estatus', true)->orderBy('banco')->get()->map(function ($empleado) use ($inicioSemana, $finSemana) {
            $nomina = $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana);

            $empleado->nomina_generada = (bool) $nomina;
            $empleado->nomina_id = $nomina ? $nomina->id : null;
            $empleado->pagado = $nomina ? $nomina->pagado : false;

            return $empleado;
        });

        $historial = Nomina::with('empleado')
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Nominas/Index', [
            'empleados' => $empleados,
            'historial' => $historial,
            'semanaActual' => $semanaActual,
            'semanasDisponibles' => $semanasDisponibles,
            'fechaCorteActual' => $fechaCorteStr,
        ]);
    }

    public function generarRecibo(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana);

        $nomina = Nomina::updateOrCreate(
            [
                'empleado_id' => $empleado->id,
                'fecha_inicio' => $inicioSemana->format('Y-m-d'),
                'fecha_fin' => $finSemana->format('Y-m-d'),
            ],
            $this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose)
        );
        $nomina->setRelation('empleado', $empleado);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $this->datosVistaRecibo($nomina, $empleado, $desglose));

        return $pdf->stream('Recibo_Semana_' . $numeroSemana . '_' . $empleado->nombre_completo . '.pdf');
    }

    public function exportarExcelIndividual(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana);

        $nomina = $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana);

        if ($nomina) {
            $nomina->update($this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose));
        } else {
            $nomina = new Nomina(array_merge([
                'empleado_id' => $empleado->id,
                'numero_semana' => $numeroSemana,
            ], $this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose)));
        }

        $nomina->setRelation('empleado', $empleado);
        $datosExcel = $this->datosVistaRecibo($nomina, $empleado, $desglose);
        $nombreArchivo = 'Recibo_Semana_' . $numeroSemana . '_' . str_replace(' ', '_', $empleado->nombre_completo) . '.xlsx';

        return Excel::download(new ReciboIndividualExport($datosExcel), $nombreArchivo);
    }

    public function reporteGlobal(Request $request, $semana)
    {
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $nombreArchivo = 'Reporte_Semana_' . $numeroSemana . '_' . $inicioSemana->format('Ymd') . '_' . $finSemana->format('Ymd') . '.xlsx';

        return Excel::download(new ReporteSemanalExport($numeroSemana, $inicioSemana, $finSemana), $nombreArchivo);
    }

    public function descargar(Nomina $nomina)
    {
        $empleado = $nomina->empleado;
        $inicioSemana = Carbon::parse($nomina->fecha_inicio)->startOfDay();
        $finSemana = Carbon::parse($nomina->fecha_fin)->endOfDay();
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana);

        $nomina->update($this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose));
        $nomina->refresh();
        $nomina->setRelation('empleado', $empleado);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $this->datosVistaRecibo($nomina, $empleado, $desglose));

        return $pdf->stream('Recibo_Semana_' . $nomina->numero_semana . '_' . $empleado->nombre_completo . '.pdf');
    }

    public function pagar(Nomina $nomina)
    {
        $nomina->update(['pagado' => !$nomina->pagado]);
        return back();
    }

    private function resolverSemanaNomina(Request $request): array
    {
        $hoy = Carbon::now();
        $martesAutomatico = $hoy->isTuesday()
            ? $hoy->copy()->endOfDay()
            : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();

        $fechaCorteStr = $request->input('fecha_corte', $martesAutomatico->format('Y-m-d'));
        $finSemana = Carbon::parse($fechaCorteStr)->endOfDay();
        $inicioSemana = $finSemana->copy()->subDays(6)->startOfDay();

        return [$inicioSemana, $finSemana, $inicioSemana->weekOfYear];
    }

    private function calcularDesgloseNomina(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana): array
    {
        $asistencias = Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [
                $inicioSemana->format('Y-m-d'),
                $finSemana->format('Y-m-d'),
            ])
            ->get();

        $horasNormales = (float) $asistencias->where('tipo_asistencia', 'Normal')->sum('horas_trabajadas');
        $horasExtra = (float) $asistencias->where('tipo_asistencia', 'Normal')->sum('horas_extra');
        $diasFalta = $asistencias->where('tipo_asistencia', 'Falta')->count();
        $diasIncapacidad = $asistencias->where('tipo_asistencia', 'Incapacidad')->count();
        $diasVacaciones = $asistencias->where('tipo_asistencia', 'Vacaciones')->count();
        $minutosTarde = (int) $asistencias->sum('minutos_tarde');

        $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);
        $esEstudiante = $this->empleadoEsEstudiante($empleado);
        $sueldoSemanal = (float) ($empleado->sueldo_semanal ?? 0);

        if (!$esEstudiante && $sueldoSemanal <= 0 && $sueldoPorHora > 0) {
            $sueldoSemanal = $sueldoPorHora * self::HORAS_BASE_SEMANA;
        }

        $tarifaBaseHora = $esEstudiante
            ? $sueldoPorHora
            : ($sueldoSemanal > 0 ? $sueldoSemanal / self::HORAS_BASE_SEMANA : 0);
        $pagoDiaPlanta = $sueldoSemanal > 0 ? $sueldoSemanal / self::DIAS_LABORALES_SEMANA : 0;

        $pagoNormal = $esEstudiante
            ? $horasNormales * $sueldoPorHora
            : $sueldoSemanal;
        $pagoExtra = $horasExtra * ($tarifaBaseHora * 2);
        $pagoIncapacidad = (!$esEstudiante && $diasIncapacidad > 0)
            ? $pagoDiaPlanta * 0.60 * $diasIncapacidad
            : 0;
        $pagoVacaciones = (!$esEstudiante && $diasVacaciones > 0)
            ? $pagoDiaPlanta * 0.25 * $diasVacaciones
            : 0;

        $descuentoFaltas = (!$esEstudiante && $diasFalta > 0)
            ? $pagoDiaPlanta * $diasFalta
            : 0;
        $descuentoRetardos = $tarifaBaseHora > 0 && $minutosTarde > 0
            ? ($tarifaBaseHora / 60) * $minutosTarde
            : 0;
        $deduccionPrestamo = $this->calcularDeduccionPrestamo($empleado);
        $descuentoImss = (float) ($empleado->descuento_imss ?? 0);
        $descuentoIsr = (float) ($empleado->descuento_isr ?? 0);
        $descuentoInfonavit = (float) ($empleado->descuento_infonavit ?? 0);

        $totalPercepciones = $pagoNormal + $pagoExtra + $pagoIncapacidad + $pagoVacaciones;
        $totalDeducciones = $descuentoFaltas
            + $descuentoRetardos
            + $deduccionPrestamo
            + $descuentoImss
            + $descuentoIsr
            + $descuentoInfonavit;
        $pagoNeto = $totalPercepciones - $totalDeducciones;

        return [
            'es_estudiante' => $esEstudiante,
            'sueldo_semanal' => round($sueldoSemanal, 2),
            'sueldo_por_hora' => round($sueldoPorHora, 2),
            'tarifa_base_hora' => round($tarifaBaseHora, 2),
            'pago_dia_planta' => round($pagoDiaPlanta, 2),
            'horas_normales' => round($horasNormales, 2),
            'horas_extra' => round($horasExtra, 2),
            'dias_falta' => $diasFalta,
            'dias_incapacidad' => $diasIncapacidad,
            'dias_vacaciones' => $diasVacaciones,
            'minutos_tarde_acumulados' => $minutosTarde,
            'pago_normal' => round($pagoNormal, 2),
            'pago_extra' => round($pagoExtra, 2),
            'pago_incapacidad' => round($pagoIncapacidad, 2),
            'pago_vacaciones' => round($pagoVacaciones, 2),
            'descuento_faltas' => round($descuentoFaltas, 2),
            'descuento_retardos' => round($descuentoRetardos, 2),
            'deduccion_prestamo' => round($deduccionPrestamo, 2),
            'descuento_imss' => round($descuentoImss, 2),
            'descuento_isr' => round($descuentoIsr, 2),
            'descuento_infonavit' => round($descuentoInfonavit, 2),
            'total_percepciones' => round($totalPercepciones, 2),
            'total_deducciones' => round($totalDeducciones, 2),
            'pago_neto' => round($pagoNeto, 2),
        ];
    }

    private function buscarNominaPeriodo(int $empleadoId, Carbon $inicioSemana, Carbon $finSemana): ?Nomina
    {
        return Nomina::where('empleado_id', $empleadoId)
            ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->first();
    }

    private function empleadoEsEstudiante(Empleado $empleado): bool
    {
        return (bool) ($empleado->es_estudiante ?? false);
    }

    private function calcularDeduccionPrestamo(Empleado $empleado): float
    {
        $saldo = (float) ($empleado->saldo_prestamo ?? 0);
        $cuota = (float) ($empleado->cuota_prestamo ?? 0);

        if ($cuota <= 0) {
            return 0;
        }

        return $saldo > 0 ? min($saldo, $cuota) : $cuota;
    }

    private function datosNominaParaGuardar(Carbon $inicioSemana, Carbon $finSemana, array $desglose): array
    {
        return [
            'numero_semana' => $inicioSemana->weekOfYear,
            'fecha_inicio' => $inicioSemana->format('Y-m-d'),
            'fecha_fin' => $finSemana->format('Y-m-d'),
            'horas_normales' => $desglose['horas_normales'],
            'horas_extra' => $desglose['horas_extra'],
            'total_percepciones' => $desglose['total_percepciones'],
            'total_deducciones' => $desglose['total_deducciones'],
            'pago_neto' => $desglose['pago_neto'],
        ];
    }

    private function datosVistaRecibo(Nomina $nomina, Empleado $empleado, array $desglose): array
    {
        return array_merge([
            'nomina' => $nomina,
            'empleado' => $empleado,
        ], $desglose);
    }
}
