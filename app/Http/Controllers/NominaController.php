<?php

namespace App\Http\Controllers;

use App\Exports\ReciboIndividualExport;
use App\Exports\ReporteSemanalExport;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Support\SemanaNomina;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class NominaController extends Controller
{
    private const DIAS_SUELDO_SEMANA = 7;
    private const HORAS_BASE_SEMANA = 56;
    private const HORAS_FALTA_COMPLETA = 9.5;
    private const UMBRAL_RETARDO_MINUTOS = 30;

    private ?bool $controlPrestamoAplicadoDisponible = null;

    public function index(Request $request)
    {
        [$inicioSemana, $finSemana, $semanaActual] = $this->resolverSemanaNomina($request);
        $semanasDisponibles = SemanaNomina::disponibles(SemanaNomina::corteActual(), 12);

        $empleados = Empleado::where('estatus', true)->orderBy('banco')->get()->map(function ($empleado) use ($inicioSemana, $finSemana) {
            $nomina = $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana);
            $ajustes = $this->ajustesDesdeNomina($nomina, $empleado);
            $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

            $empleado->nomina_generada = (bool) $nomina;
            $empleado->nomina_id = $nomina ? $nomina->id : null;
            $empleado->pagado = $nomina ? $nomina->pagado : false;
            $empleado->ajustes_nomina = $this->resumenAjustesNomina($empleado, $nomina, $desglose, $inicioSemana);

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
            'fechaCorteActual' => $finSemana->format('Y-m-d'),
        ]);
    }

    public function actualizarAjustes(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana] = $this->resolverSemanaNomina($request);
        $ajustes = $this->ajustesDesdeRequest($request, $empleado);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);

        return back()->with('success', 'Ajustes de nomina guardados y recalculados.');
    }

    public function generarRecibo(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $ajustes = $this->resolverAjustesParaPeriodo($request, $empleado, $inicioSemana, $finSemana);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);
        $nomina->setRelation('empleado', $empleado);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $this->datosVistaRecibo($nomina, $empleado, $desglose));

        return $pdf->stream('Recibo_Semana_' . $numeroSemana . '_' . $empleado->nombre_completo . '.pdf');
    }

    public function exportarExcelIndividual(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $ajustes = $this->resolverAjustesParaPeriodo($request, $empleado, $inicioSemana, $finSemana);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);

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
        $ajustes = $this->ajustesDesdeNomina($nomina, $empleado);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);
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
        return SemanaNomina::desdeCorte($request->input('fecha_corte'));
    }

    private function calcularDesgloseNomina(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana, array $ajustes = []): array
    {
        $ajustes = array_merge($this->ajustesPorDefecto($empleado), $ajustes);
        $asistencias = Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [
                $inicioSemana->format('Y-m-d'),
                $finSemana->format('Y-m-d'),
            ])
            ->get();

        $horasNormales = (float) $asistencias->where('tipo_asistencia', 'Normal')->sum('horas_trabajadas');
        $horasExtraPeriodo = (float) $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->sum(fn ($asistencia) => $this->horasExtraRedondeadas($asistencia));
        $horasExtraMiercolesAnterior = $this->horasExtraMiercolesAnterior($empleado, $inicioSemana);
        $horasExtra = $horasExtraPeriodo + $horasExtraMiercolesAnterior;
        $diasFalta = $asistencias->where('tipo_asistencia', 'Falta')->count();
        $diasIncapacidad = $asistencias->where('tipo_asistencia', 'Incapacidad')->count();
        $diasVacacionesDetectadas = $asistencias->where('tipo_asistencia', 'Vacaciones')->count();
        $diasVacacionesPagadas = $ajustes['dias_vacaciones_pagadas'] === null
            ? (float) $diasVacacionesDetectadas
            : max(0, (float) $ajustes['dias_vacaciones_pagadas']);
        $minutosTarde = (int) $asistencias->sum('minutos_tarde');
        $minutosTardeDescontables = (int) $asistencias
            ->filter(fn ($asistencia) => (int) $asistencia->minutos_tarde >= self::UMBRAL_RETARDO_MINUTOS)
            ->sum('minutos_tarde');

        $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);
        $esEstudiante = $this->empleadoEsEstudiante($empleado);
        $sueldoSemanal = (float) ($empleado->sueldo_semanal ?? 0);

        if (!$esEstudiante && $sueldoSemanal <= 0 && $sueldoPorHora > 0) {
            $sueldoSemanal = $sueldoPorHora * self::HORAS_BASE_SEMANA;
        }

        $tarifaBaseHora = $esEstudiante
            ? $sueldoPorHora
            : ($sueldoSemanal > 0 ? $sueldoSemanal / self::HORAS_BASE_SEMANA : 0);
        $pagoDiaPlanta = $sueldoSemanal > 0 ? $sueldoSemanal / self::DIAS_SUELDO_SEMANA : 0;

        $faltasPagadas = min((int) $ajustes['faltas_pagadas'], $diasFalta);
        $faltasDescontables = max(0, $diasFalta - $faltasPagadas);
        $horasAdeudoGeneradas = $faltasPagadas * self::HORAS_FALTA_COMPLETA;
        $horasAdeudoDescontadas = min((float) $ajustes['horas_adeudo_descontadas'], $horasExtra);
        $horasExtraPagadas = max(0, $horasExtra - $horasAdeudoDescontadas);

        $pagoNormal = $esEstudiante
            ? ($horasNormales * $sueldoPorHora) + ($faltasPagadas * self::HORAS_FALTA_COMPLETA * $sueldoPorHora)
            : $sueldoSemanal;
        $pagoExtra = $horasExtraPagadas * ($tarifaBaseHora * 2);
        $pagoIncapacidad = (!$esEstudiante && $diasIncapacidad > 0)
            ? $pagoDiaPlanta * 0.60 * $diasIncapacidad
            : 0;
        $pagoVacaciones = (!$esEstudiante && $diasVacacionesPagadas > 0)
            ? $pagoDiaPlanta * 1.25 * $diasVacacionesPagadas
            : 0;
        $prestamoOtorgado = (float) $ajustes['prestamo_otorgado'];

        $descuentoFaltas = (!$esEstudiante && $faltasDescontables > 0)
            ? $tarifaBaseHora * self::HORAS_FALTA_COMPLETA * $faltasDescontables
            : 0;
        $descuentoRetardos = $tarifaBaseHora > 0 && $minutosTardeDescontables > 0
            ? ($tarifaBaseHora / 60) * $minutosTardeDescontables
            : 0;
        $prestamoDescuento = (float) $ajustes['prestamo_descuento'];
        $deduccionManual = (float) $ajustes['deduccion_manual'];
        $descuentoImss = (float) ($empleado->descuento_imss ?? 0);
        $descuentoIsr = (float) ($empleado->descuento_isr ?? 0);
        $descuentoInfonavit = (float) ($empleado->descuento_infonavit ?? 0);

        $totalPercepciones = $pagoNormal + $pagoExtra + $pagoIncapacidad + $pagoVacaciones + $prestamoOtorgado;
        $totalDeducciones = $descuentoFaltas
            + $descuentoRetardos
            + $prestamoDescuento
            + $deduccionManual
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
            'horas_extra_periodo' => round($horasExtraPeriodo, 2),
            'horas_extra_miercoles_anterior' => round($horasExtraMiercolesAnterior, 2),
            'horas_extra_pagadas' => round($horasExtraPagadas, 2),
            'horas_adeudo_generadas' => round($horasAdeudoGeneradas, 2),
            'horas_adeudo_descontadas' => round($horasAdeudoDescontadas, 2),
            'dias_falta' => $diasFalta,
            'dias_falta_pagados' => $faltasPagadas,
            'dias_falta_descontables' => $faltasDescontables,
            'dias_incapacidad' => $diasIncapacidad,
            'dias_vacaciones' => round($diasVacacionesPagadas, 2),
            'dias_vacaciones_detectadas' => $diasVacacionesDetectadas,
            'dias_vacaciones_pagadas' => round($diasVacacionesPagadas, 2),
            'minutos_tarde_acumulados' => $minutosTarde,
            'minutos_tarde_descontables' => $minutosTardeDescontables,
            'pago_normal' => round($pagoNormal, 2),
            'pago_extra' => round($pagoExtra, 2),
            'pago_incapacidad' => round($pagoIncapacidad, 2),
            'pago_vacaciones' => round($pagoVacaciones, 2),
            'prestamo_otorgado' => round($prestamoOtorgado, 2),
            'descuento_faltas' => round($descuentoFaltas, 2),
            'descuento_retardos' => round($descuentoRetardos, 2),
            'prestamo_descuento' => round($prestamoDescuento, 2),
            'deduccion_prestamo' => round($prestamoDescuento, 2),
            'deduccion_manual' => round($deduccionManual, 2),
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

    private function guardarNominaPeriodo(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana, array $desglose): Nomina
    {
        return DB::transaction(function () use ($empleado, $inicioSemana, $finSemana, $desglose) {
            $nomina = Nomina::where('empleado_id', $empleado->id)
                ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
                ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            $controlPrestamoAplicado = $this->controlPrestamoAplicadoDisponible();
            [$prestamoOtorgadoAnterior, $prestamoDescuentoAnterior] = $this->prestamoAplicadoAnterior($nomina, $controlPrestamoAplicado);
            $datos = $this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose);

            if ($nomina) {
                $nomina->fill($datos);
                $nomina->save();
            } else {
                $nomina = Nomina::create(array_merge([
                    'empleado_id' => $empleado->id,
                ], $datos));
            }

            $this->aplicarMovimientoPrestamoEmpleado(
                $empleado,
                (float) $desglose['prestamo_otorgado'],
                $prestamoOtorgadoAnterior,
                (float) $desglose['prestamo_descuento'],
                $prestamoDescuentoAnterior
            );

            if ($controlPrestamoAplicado) {
                $nomina->forceFill([
                    'prestamo_saldo_aplicado_otorgado' => (float) $desglose['prestamo_otorgado'],
                    'prestamo_saldo_aplicado_descuento' => (float) $desglose['prestamo_descuento'],
                    'prestamo_saldo_aplicado_at' => now(),
                ])->save();
            }

            return $nomina->fresh();
        });
    }

    private function prestamoAplicadoAnterior(?Nomina $nomina, bool $controlPrestamoAplicado): array
    {
        if (!$nomina) {
            return [0.0, 0.0];
        }

        if ($controlPrestamoAplicado) {
            return [
                (float) ($nomina->prestamo_saldo_aplicado_otorgado ?? 0),
                (float) ($nomina->prestamo_saldo_aplicado_descuento ?? 0),
            ];
        }

        return [
            (float) ($nomina->prestamo_otorgado ?? 0),
            (float) ($nomina->prestamo_descuento ?? 0),
        ];
    }

    private function aplicarMovimientoPrestamoEmpleado(
        Empleado $empleado,
        float $prestamoOtorgadoActual,
        float $prestamoOtorgadoAnterior,
        float $prestamoDescuentoActual,
        float $prestamoDescuentoAnterior
    ): void {
        $diferenciaOtorgada = $prestamoOtorgadoActual - $prestamoOtorgadoAnterior;
        $diferenciaDescontada = $prestamoDescuentoActual - $prestamoDescuentoAnterior;
        $movimientoSaldo = round($diferenciaOtorgada - $diferenciaDescontada, 2);

        if (abs($movimientoSaldo) < 0.01) {
            return;
        }

        $empleadoPrestamo = Empleado::whereKey($empleado->id)->lockForUpdate()->firstOrFail();
        $saldoActual = (float) ($empleadoPrestamo->saldo_prestamo ?? 0);

        $empleadoPrestamo->forceFill([
            'saldo_prestamo' => max(0, round($saldoActual + $movimientoSaldo, 2)),
        ])->save();

        $empleado->refresh();
    }

    private function horasExtraMiercolesAnterior(Empleado $empleado, Carbon $inicioSemana): float
    {
        $miercolesAnterior = $inicioSemana->copy()->subDay();

        if (!$miercolesAnterior->isWednesday()) {
            return 0;
        }

        $asistencia = Asistencia::where('empleado_id', $empleado->id)
            ->whereDate('fecha', $miercolesAnterior->format('Y-m-d'))
            ->where('tipo_asistencia', 'Normal')
            ->first();

        return $asistencia ? $this->horasExtraRedondeadas($asistencia) : 0;
    }

    private function horasExtraRedondeadas(Asistencia $asistencia): float
    {
        if ($asistencia->tipo_asistencia !== 'Normal' || !$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return 0;
        }

        $fecha = Carbon::parse($asistencia->fecha);
        $entrada = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_entrada);
        $salida = Carbon::parse($fecha->format('Y-m-d') . ' ' . $asistencia->hora_salida);
        $horaOficial = Carbon::parse($fecha->format('Y-m-d') . ' 08:00:00');

        if ($fecha->isSaturday()) {
            $inicioSabado = $entrada->lessThan($horaOficial) ? $horaOficial : $entrada;

            return $this->redondearHoraExtra($inicioSabado->diffInMinutes($salida) / 60);
        }

        $limiteNormal = Carbon::parse($fecha->format('Y-m-d') . ' 17:30:00');

        if (!$salida->greaterThan($limiteNormal)) {
            return 0;
        }

        return $this->redondearHoraExtra($limiteNormal->diffInMinutes($salida) / 60);
    }

    private function redondearHoraExtra(float $horas): float
    {
        return max(0, floor($horas));
    }

    private function resolverAjustesParaPeriodo(Request $request, Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana): array
    {
        if ($this->requestTieneAjustesNomina($request)) {
            return $this->ajustesDesdeRequest($request, $empleado);
        }

        return $this->ajustesDesdeNomina(
            $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana),
            $empleado
        );
    }

    private function requestTieneAjustesNomina(Request $request): bool
    {
        foreach (['prestamo_otorgado', 'prestamo_descuento', 'deduccion_manual', 'faltas_pagadas', 'horas_adeudo_descontadas', 'dias_vacaciones_pagadas'] as $campo) {
            if ($request->has($campo)) {
                return true;
            }
        }

        return false;
    }

    private function ajustesDesdeRequest(Request $request, Empleado $empleado): array
    {
        $validated = $request->validate([
            'fecha_corte' => 'nullable|date',
            'prestamo_otorgado' => 'nullable|numeric|min:0',
            'prestamo_descuento' => 'nullable|numeric|min:0',
            'deduccion_manual' => 'nullable|numeric|min:0',
            'faltas_pagadas' => 'nullable|integer|min:0',
            'horas_adeudo_descontadas' => 'nullable|numeric|min:0',
            'dias_vacaciones_pagadas' => 'nullable|numeric|min:0',
        ]);

        $default = $this->ajustesPorDefecto($empleado);

        return [
            'prestamo_otorgado' => (float) ($validated['prestamo_otorgado'] ?? $default['prestamo_otorgado']),
            'prestamo_descuento' => (float) ($validated['prestamo_descuento'] ?? $default['prestamo_descuento']),
            'deduccion_manual' => (float) ($validated['deduccion_manual'] ?? $default['deduccion_manual']),
            'faltas_pagadas' => (int) ($validated['faltas_pagadas'] ?? $default['faltas_pagadas']),
            'horas_adeudo_descontadas' => (float) ($validated['horas_adeudo_descontadas'] ?? $default['horas_adeudo_descontadas']),
            'dias_vacaciones_pagadas' => array_key_exists('dias_vacaciones_pagadas', $validated)
                ? (float) $validated['dias_vacaciones_pagadas']
                : $default['dias_vacaciones_pagadas'],
        ];
    }

    private function ajustesDesdeNomina(?Nomina $nomina, Empleado $empleado): array
    {
        if (!$nomina) {
            return $this->ajustesPorDefecto($empleado);
        }

        return [
            'prestamo_otorgado' => (float) ($nomina->prestamo_otorgado ?? 0),
            'prestamo_descuento' => (float) ($nomina->prestamo_descuento ?? $this->deduccionPrestamoSugerida($empleado)),
            'deduccion_manual' => (float) ($nomina->deduccion_manual ?? 0),
            'faltas_pagadas' => (int) ($nomina->faltas_pagadas ?? 0),
            'horas_adeudo_descontadas' => (float) ($nomina->horas_adeudo_descontadas ?? 0),
            'dias_vacaciones_pagadas' => $nomina->dias_vacaciones_pagadas !== null
                ? (float) $nomina->dias_vacaciones_pagadas
                : null,
        ];
    }

    private function ajustesPorDefecto(Empleado $empleado): array
    {
        return [
            'prestamo_otorgado' => 0,
            'prestamo_descuento' => $this->deduccionPrestamoSugerida($empleado),
            'deduccion_manual' => 0,
            'faltas_pagadas' => 0,
            'horas_adeudo_descontadas' => 0,
            'dias_vacaciones_pagadas' => null,
        ];
    }

    private function resumenAjustesNomina(Empleado $empleado, ?Nomina $nomina, array $desglose, Carbon $inicioSemana): array
    {
        $saldoAnterior = $this->saldoHorasAdeudo($empleado->id, $inicioSemana);
        $saldoFinal = max(0, $saldoAnterior + $desglose['horas_adeudo_generadas'] - $desglose['horas_adeudo_descontadas']);
        [$prestamoOtorgadoGuardado, $prestamoDescuentoGuardado] = $this->prestamoAplicadoAnterior(
            $nomina,
            $this->controlPrestamoAplicadoDisponible()
        );
        $saldoPrestamoActual = round((float) ($empleado->saldo_prestamo ?? 0), 2);
        $saldoPrestamoEstimado = max(0, round(
            $saldoPrestamoActual
                + ($desglose['prestamo_otorgado'] - $prestamoOtorgadoGuardado)
                - ($desglose['prestamo_descuento'] - $prestamoDescuentoGuardado),
            2
        ));

        return [
            'prestamo_otorgado' => $desglose['prestamo_otorgado'],
            'prestamo_descuento' => $desglose['prestamo_descuento'],
            'prestamo_otorgado_guardado' => round($prestamoOtorgadoGuardado, 2),
            'prestamo_descuento_guardado' => round($prestamoDescuentoGuardado, 2),
            'saldo_prestamo_actual' => $saldoPrestamoActual,
            'saldo_prestamo_estimado' => $saldoPrestamoEstimado,
            'deduccion_manual' => $desglose['deduccion_manual'],
            'faltas_detectadas' => $desglose['dias_falta'],
            'faltas_pagadas' => $desglose['dias_falta_pagados'],
            'faltas_descontables' => $desglose['dias_falta_descontables'],
            'dias_vacaciones_detectadas' => $desglose['dias_vacaciones_detectadas'],
            'dias_vacaciones_pagadas' => $desglose['dias_vacaciones_pagadas'],
            'pago_vacaciones' => $desglose['pago_vacaciones'],
            'pago_dia_planta' => $desglose['pago_dia_planta'],
            'horas_extra_detectadas' => $desglose['horas_extra'],
            'horas_extra_periodo' => $desglose['horas_extra_periodo'],
            'horas_extra_miercoles_anterior' => $desglose['horas_extra_miercoles_anterior'],
            'horas_extra_pagadas' => $desglose['horas_extra_pagadas'],
            'horas_adeudo_descontadas' => $desglose['horas_adeudo_descontadas'],
            'horas_adeudo_generadas' => $desglose['horas_adeudo_generadas'],
            'saldo_horas_adeudo_anterior' => round($saldoAnterior, 2),
            'saldo_horas_adeudo_final' => round($saldoFinal, 2),
            'minutos_tarde' => $desglose['minutos_tarde_acumulados'],
            'minutos_tarde_descontables' => $desglose['minutos_tarde_descontables'],
            'total_percepciones' => $desglose['total_percepciones'],
            'total_deducciones' => $desglose['total_deducciones'],
            'pago_neto' => $desglose['pago_neto'],
        ];
    }

    private function saldoHorasAdeudo(int $empleadoId, Carbon $inicioSemana): float
    {
        $consulta = Nomina::where('empleado_id', $empleadoId)
            ->whereDate('fecha_inicio', '<', $inicioSemana->format('Y-m-d'));

        return max(0, round(
            (float) $consulta->sum('horas_adeudo_generadas') - (float) $consulta->sum('horas_adeudo_descontadas'),
            2
        ));
    }

    private function empleadoEsEstudiante(Empleado $empleado): bool
    {
        return (bool) ($empleado->es_estudiante ?? false);
    }

    private function deduccionPrestamoSugerida(Empleado $empleado): float
    {
        $saldo = (float) ($empleado->saldo_prestamo ?? 0);
        $cuota = (float) ($empleado->cuota_prestamo ?? 0);

        if ($cuota <= 0) {
            return 0;
        }

        return $saldo > 0 ? min($saldo, $cuota) : 0;
    }

    private function datosNominaParaGuardar(Carbon $inicioSemana, Carbon $finSemana, array $desglose): array
    {
        return [
            'numero_semana' => $inicioSemana->weekOfYear,
            'fecha_inicio' => $inicioSemana->format('Y-m-d'),
            'fecha_fin' => $finSemana->format('Y-m-d'),
            'horas_normales' => $desglose['horas_normales'],
            'horas_extra' => $desglose['horas_extra'],
            'horas_extra_pagadas' => $desglose['horas_extra_pagadas'],
            'prestamo_otorgado' => $desglose['prestamo_otorgado'],
            'prestamo_descuento' => $desglose['prestamo_descuento'],
            'deduccion_manual' => $desglose['deduccion_manual'],
            'faltas_pagadas' => $desglose['dias_falta_pagados'],
            'dias_vacaciones_pagadas' => $desglose['dias_vacaciones_pagadas'],
            'horas_adeudo_generadas' => $desglose['horas_adeudo_generadas'],
            'horas_adeudo_descontadas' => $desglose['horas_adeudo_descontadas'],
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

    private function controlPrestamoAplicadoDisponible(): bool
    {
        if ($this->controlPrestamoAplicadoDisponible !== null) {
            return $this->controlPrestamoAplicadoDisponible;
        }

        return $this->controlPrestamoAplicadoDisponible = Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_otorgado')
            && Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_descuento')
            && Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_at');
    }
}
