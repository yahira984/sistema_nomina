<?php

namespace App\Http\Controllers;

use App\Exports\ReciboIndividualExport;
use App\Exports\ReporteSemanalExport;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Support\ReglasNominaEmpleado;
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
    private const UMBRAL_RETARDO_SEMANAL_MINUTOS = 30;
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

    public function generarRecibosMasivos(Request $request)
    {
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $empleadoIds = $this->empleadoIdsDesdeRequest($request);

        $empleados = Empleado::where('estatus', true)
            ->when(count($empleadoIds) > 0, fn ($query) => $query->whereIn('id', $empleadoIds))
            ->orderBy('banco')
            ->orderBy('nombre_completo')
            ->get();

        abort_if($empleados->isEmpty(), 404, 'No hay empleados activos para imprimir en este periodo.');

        $recibos = $empleados->map(function ($empleado) use ($inicioSemana, $finSemana) {
            $ajustes = $this->ajustesDesdeNomina(
                $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana),
                $empleado
            );
            $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);
            $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);

            $nomina->setRelation('empleado', $empleado);

            return $this->datosVistaRecibo($nomina, $empleado, $desglose);
        })->values();

        $pdf = Pdf::loadView('pdf.recibos_nomina_masivos', [
            'recibos' => $recibos,
        ]);

        $sufijo = count($empleadoIds) > 0 ? 'seleccionados' : 'todos';

        return $pdf->stream('Recibos_Semana_' . $numeroSemana . '_' . $sufijo . '.pdf');
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
        $pagado = DB::transaction(function () use ($nomina) {
            $nomina = Nomina::whereKey($nomina->id)->lockForUpdate()->firstOrFail();
            $empleado = Empleado::findOrFail($nomina->empleado_id);
            $controlPrestamoAplicado = $this->controlPrestamoAplicadoDisponible();
            [$prestamoOtorgadoAplicado, $prestamoDescuentoAplicado] = $this->prestamoAplicadoAnterior($nomina, $controlPrestamoAplicado);

            if ($nomina->pagado) {
                $this->aplicarMovimientoPrestamoEmpleado(
                    $empleado,
                    0,
                    $prestamoOtorgadoAplicado,
                    0,
                    $prestamoDescuentoAplicado
                );

                $datos = ['pagado' => false];

                if ($controlPrestamoAplicado) {
                    $datos = array_merge($datos, [
                        'prestamo_saldo_aplicado_otorgado' => 0,
                        'prestamo_saldo_aplicado_descuento' => 0,
                        'prestamo_saldo_aplicado_at' => null,
                    ]);
                }

                $nomina->forceFill($datos)->save();

                return false;
            }

            $prestamoOtorgadoNomina = (float) ($nomina->prestamo_otorgado ?? 0);
            $prestamoDescuentoNomina = (float) ($nomina->prestamo_descuento ?? 0);

            $this->aplicarMovimientoPrestamoEmpleado(
                $empleado,
                $prestamoOtorgadoNomina,
                $prestamoOtorgadoAplicado,
                $prestamoDescuentoNomina,
                $prestamoDescuentoAplicado
            );

            $datos = ['pagado' => true];

            if ($controlPrestamoAplicado) {
                $datos = array_merge($datos, [
                    'prestamo_saldo_aplicado_otorgado' => $prestamoOtorgadoNomina,
                    'prestamo_saldo_aplicado_descuento' => $prestamoDescuentoNomina,
                    'prestamo_saldo_aplicado_at' => now(),
                ]);
            }

            $nomina->forceFill($datos)->save();

            return true;
        });

        return back()->with(
            'success',
            $pagado
                ? 'Nomina marcada como pagada. Prestamos aplicados al saldo del empleado.'
                : 'Nomina regresada a pendiente. Prestamos revertidos del saldo del empleado.'
        );
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
            ->reject(fn ($asistencia) => $this->esMiercolesDeCorte($asistencia, $finSemana))
            ->sum(fn ($asistencia) => $this->horasExtraRedondeadas($asistencia));
        $horasExtraMiercolesAnterior = $this->horasExtraMiercolesAnterior($empleado, $inicioSemana);
        $horasExtra = $horasExtraPeriodo + $horasExtraMiercolesAnterior;

        if (ReglasNominaEmpleado::sinHorasExtra($empleado)) {
            $horasExtraPeriodo = 0;
            $horasExtraMiercolesAnterior = 0;
            $horasExtra = 0;
        }

        $diasFalta = $asistencias
            ->where('tipo_asistencia', 'Falta')
            ->filter(fn ($asistencia) => $this->esDiaFaltaDescontable($asistencia->fecha))
            ->count();
        $diasIncapacidad = $asistencias->where('tipo_asistencia', 'Incapacidad')->count();
        $diasVacacionesDetectadas = $asistencias->where('tipo_asistencia', 'Vacaciones')->count();
        $diasVacacionesPagadas = $ajustes['dias_vacaciones_pagadas'] === null
            ? (float) $diasVacacionesDetectadas
            : max(0, (float) $ajustes['dias_vacaciones_pagadas']);
        $minutosTarde = (int) $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->filter(fn ($asistencia) => $this->esRetardoDescontable($asistencia))
            ->sum('minutos_tarde');
        $minutosTardeDescontables = $minutosTarde >= self::UMBRAL_RETARDO_SEMANAL_MINUTOS
            ? $minutosTarde
            : 0;

        $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);
        $esEstudiante = $this->empleadoEsEstudiante($empleado);
        $pagoPorHoraTopado = ReglasNominaEmpleado::pagoPorHoraTopado($empleado);
        $sueldoSemanal = (float) ($empleado->sueldo_semanal ?? 0);

        if ($esEstudiante || ReglasNominaEmpleado::sinRetardos($empleado)) {
            $minutosTardeDescontables = 0;
        }

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

        if ($pagoPorHoraTopado) {
            $horasExtraParaTope = $horasExtraPagadas;
            $horasParaPagoPorHora = $horasNormales + $horasExtraParaTope + ($faltasPagadas * self::HORAS_FALTA_COMPLETA);
            $topeHorasPagables = max(
                0,
                ReglasNominaEmpleado::TOPE_HORAS_POR_HORA - ($faltasDescontables * self::HORAS_FALTA_COMPLETA)
            );
            $pagoNormal = min($horasParaPagoPorHora, $topeHorasPagables) * $sueldoPorHora;
            $pagoExtra = 0;
            $horasExtraPagadas = 0;
        } else {
            $pagoNormal = $esEstudiante
                ? ($horasNormales * $sueldoPorHora) + ($faltasPagadas * self::HORAS_FALTA_COMPLETA * $sueldoPorHora)
                : $sueldoSemanal;
            $pagoExtra = $horasExtraPagadas * ($tarifaBaseHora * 2);
        }
        $pagoIncapacidad = (!$esEstudiante && $diasIncapacidad > 0)
            ? $pagoDiaPlanta * 0.60 * $diasIncapacidad
            : 0;
        $pagoVacaciones = (!$esEstudiante && $diasVacacionesPagadas > 0)
            ? $pagoDiaPlanta * 1.25 * $diasVacacionesPagadas
            : 0;
        $prestamoOtorgado = (float) $ajustes['prestamo_otorgado'];

        $descuentoFaltas = (!$esEstudiante && !$pagoPorHoraTopado && $faltasDescontables > 0)
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
            'pago_por_hora_topado' => $pagoPorHoraTopado,
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

    private function empleadoIdsDesdeRequest(Request $request): array
    {
        $empleadoIds = $request->input('empleado_ids', []);

        if (is_string($empleadoIds)) {
            $empleadoIds = explode(',', $empleadoIds);
        }

        if (!is_array($empleadoIds)) {
            return [];
        }

        return collect($empleadoIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function guardarNominaPeriodo(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana, array $desglose): Nomina
    {
        return DB::transaction(function () use ($empleado, $inicioSemana, $finSemana, $desglose) {
            $nomina = Nomina::where('empleado_id', $empleado->id)
                ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
                ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            $datos = $this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose);

            if ($nomina) {
                $nomina->fill($datos);
                $nomina->save();
            } else {
                $nomina = Nomina::create(array_merge([
                    'empleado_id' => $empleado->id,
                ], $datos));
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
            if ($nomina->prestamo_saldo_aplicado_at) {
                return [
                    (float) ($nomina->prestamo_saldo_aplicado_otorgado ?? 0),
                    (float) ($nomina->prestamo_saldo_aplicado_descuento ?? 0),
                ];
            }

            if (!$nomina->pagado) {
                return [0.0, 0.0];
            }

            return [
                (float) ($nomina->prestamo_otorgado ?? 0),
                (float) ($nomina->prestamo_descuento ?? 0),
            ];
        }

        if (!$nomina->pagado) {
            return [0.0, 0.0];
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
        $nuevoSaldo = max(0, round($saldoActual + $movimientoSaldo, 2));
        $datosEmpleado = [
            'saldo_prestamo' => $nuevoSaldo,
        ];

        if ($nuevoSaldo <= 0) {
            $datosEmpleado['cuota_prestamo'] = 0;
        } elseif ((float) ($empleadoPrestamo->cuota_prestamo ?? 0) <= 0 && $prestamoDescuentoAnterior > 0) {
            $datosEmpleado['cuota_prestamo'] = round($prestamoDescuentoAnterior, 2);
        }

        $empleadoPrestamo->forceFill($datosEmpleado)->save();

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

    private function esMiercolesDeCorte(Asistencia $asistencia, Carbon $finSemana): bool
    {
        $fecha = Carbon::parse($asistencia->fecha);

        return $fecha->isWednesday() && $fecha->isSameDay($finSemana);
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

            return $this->redondearHoraExtraSabado($inicioSabado->diffInMinutes($salida) / 60);
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

    private function redondearHoraExtraSabado(float $horas): float
    {
        return max(0, round($horas));
    }

    private function esDiaFaltaDescontable($fecha): bool
    {
        return !Carbon::parse($fecha)->isWeekend();
    }

    private function esRetardoDescontable(Asistencia $asistencia): bool
    {
        if (Carbon::parse($asistencia->fecha)->isWeekend() || !$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return false;
        }

        $fechaBase = Carbon::parse($asistencia->fecha)->format('Y-m-d');
        $entrada = Carbon::parse($fechaBase . ' ' . $asistencia->hora_entrada);
        $salida = Carbon::parse($fechaBase . ' ' . $asistencia->hora_salida);
        $horaOficial = Carbon::parse($fechaBase . ' 08:00:00');
        $limiteMarcaSalida = Carbon::parse($fechaBase . ' 16:00:00');

        return $salida->greaterThan($entrada)
            && $entrada->greaterThan($horaOficial)
            && $entrada->lessThan($limiteMarcaSalida);
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
            'pago_por_hora_topado' => $desglose['pago_por_hora_topado'],
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
