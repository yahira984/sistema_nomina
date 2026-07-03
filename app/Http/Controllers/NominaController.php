<?php

namespace App\Http\Controllers;

use App\Exports\ReciboIndividualExport;
use App\Exports\DiferenciaImssExport;
use App\Exports\ReporteSemanalExport;
use App\Models\Asistencia;
use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Services\FirebaseSyncService;
use App\Support\ReglasNominaEmpleado;
use App\Support\SemanaNomina;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
        $empleadosBase = $this->empleadosBaseNomina();
        $empleadoIds = $empleadosBase->pluck('id')->all();
        $nominasPeriodo = $this->nominasPeriodoPorEmpleado($empleadoIds, $inicioSemana, $finSemana);
        $asistenciasPeriodo = $this->asistenciasPeriodoPorEmpleado($empleadoIds, $inicioSemana, $finSemana);
        $asistenciasMiercolesAnterior = $this->asistenciasMiercolesAnteriorPorEmpleado($empleadoIds, $inicioSemana);
        $saldosHorasAdeudo = $this->saldosHorasAdeudoPorEmpleado($empleadoIds, $inicioSemana);
        $historialPayload = $this->historialNominas($request);

        $empleados = $empleadosBase->map(function (Empleado $empleado) use (
            $inicioSemana,
            $finSemana,
            $nominasPeriodo,
            $asistenciasPeriodo,
            $asistenciasMiercolesAnterior,
            $saldosHorasAdeudo
        ) {
            $nomina = $nominasPeriodo->get($empleado->id);
            $ajustes = $this->ajustesDesdeNomina($nomina, $empleado);
            $desglose = $this->calcularDesgloseNomina(
                $empleado,
                $inicioSemana,
                $finSemana,
                $ajustes,
                $asistenciasPeriodo->get($empleado->id, collect()),
                $asistenciasMiercolesAnterior->get($empleado->id),
                true
            );

            return array_merge($this->empleadoNominaPayload($empleado), [
                'nomina_generada' => (bool) $nomina,
                'nomina_id' => $nomina ? $nomina->id : null,
                'pagado' => $nomina ? (bool) $nomina->pagado : false,
                'ajustes_nomina' => $this->resumenAjustesNomina(
                    $empleado,
                    $nomina,
                    $desglose,
                    $inicioSemana,
                    (float) ($saldosHorasAdeudo[$empleado->id] ?? 0)
                ),
            ]);
        });

        return Inertia::render('Nominas/Index', [
            'empleados' => $empleados,
            'historial' => $historialPayload['data'],
            'historialMeta' => $historialPayload['meta'],
            'filtros' => $historialPayload['filtros'],
            'semanaActual' => $semanaActual,
            'semanasDisponibles' => $semanasDisponibles,
            'fechaCorteActual' => $finSemana->format('Y-m-d'),
        ]);
    }

    public function actualizarAjustes(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana] = $this->resolverSemanaNomina($request);
        $estadoCaptura = $this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana);

        if (!$estadoCaptura['lista_para_nomina']) {
            return back()->withErrors(['asistencia' => $estadoCaptura['mensaje']]);
        }

        $ajustes = $this->ajustesDesdeRequest($request, $empleado);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);

        return back()->with('success', 'Ajustes de nomina guardados y recalculados.');
    }

    public function generarRecibo(Request $request, $empleado_id)
    {
        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $this->abortarSiAsistenciaPendiente($empleado, $inicioSemana, $finSemana);

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
        $this->abortarSiAsistenciaPendiente($empleado, $inicioSemana, $finSemana);

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
            ->orderByRaw('CAST(COALESCE(numero_empleado, numero_empleado_baja, id) AS UNSIGNED) ASC')
            ->orderBy('id')
            ->get();

        abort_if($empleados->isEmpty(), 404, 'No hay empleados activos para imprimir en este periodo.');

        $pendientesCaptura = $empleados
            ->filter(fn (Empleado $empleado) => !$this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana)['lista_para_nomina'])
            ->count();

        abort_if($pendientesCaptura > 0, 422, "{$pendientesCaptura} empleado(s) tienen asistencia pendiente de captura para este periodo.");

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

    public function reporteDiferenciaImss(Request $request, $semana)
    {
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $nombreArchivo = 'Diferencia_IMSS_Semana_' . $numeroSemana . '_' . $inicioSemana->format('Ymd') . '_' . $finSemana->format('Ymd') . '.xlsx';

        return Excel::download(new DiferenciaImssExport($numeroSemana, $inicioSemana, $finSemana), $nombreArchivo);
    }

    public function recibosDiferenciaImss(Request $request, $semana)
    {
        [$inicioSemana, $finSemana, $numeroSemana] = $this->resolverSemanaNomina($request);
        $recibos = $this->recibosDiferenciaImssPeriodo($inicioSemana, $finSemana);

        $pdf = Pdf::loadView('pdf.recibos_diferencia_imss', [
            'recibos' => $recibos,
        ]);

        return $pdf->stream('Recibos_Diferencia_IMSS_Semana_' . $numeroSemana . '.pdf');
    }

    public function actualizarDiferenciaImss(Request $request, $empleado_id)
    {
        $validated = $request->validate([
            'fecha_corte' => 'nullable|date',
            'deposito_imss' => 'nullable|numeric|min:0',
        ]);

        $empleado = Empleado::findOrFail($empleado_id);
        [$inicioSemana, $finSemana] = $this->resolverSemanaNomina($request);
        $estadoCaptura = $this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana);

        if (!$estadoCaptura['lista_para_nomina']) {
            return back()->withErrors(['asistencia' => $estadoCaptura['mensaje']]);
        }

        $nomina = $this->buscarNominaPeriodo($empleado->id, $inicioSemana, $finSemana);

        if (!$nomina) {
            $ajustes = $this->ajustesPorDefecto($empleado);
            $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);
            $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);
        }

        $nomina->forceFill([
            'deposito_imss' => round((float) ($validated['deposito_imss'] ?? 0), 2),
        ])->save();

        return back()->with('success', 'Deposito IMSS guardado para esta semana.');
    }

    public function descargar(Nomina $nomina)
    {
        $empleado = $nomina->empleado;
        $inicioSemana = Carbon::parse($nomina->fecha_inicio)->startOfDay();
        $finSemana = Carbon::parse($nomina->fecha_fin)->endOfDay();
        $this->abortarSiAsistenciaPendiente($empleado, $inicioSemana, $finSemana);

        $ajustes = $this->ajustesDesdeNomina($nomina, $empleado);
        $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

        $nomina = $this->guardarNominaPeriodo($empleado, $inicioSemana, $finSemana, $desglose);
        $nomina->setRelation('empleado', $empleado);

        $pdf = Pdf::loadView('pdf.recibo_nomina', $this->datosVistaRecibo($nomina, $empleado, $desglose));

        return $pdf->stream('Recibo_Semana_' . $nomina->numero_semana . '_' . $empleado->nombre_completo . '.pdf');
    }

    public function pagar(Request $request, Nomina $nomina)
    {
        $resultadoPago = $this->procesarCambioPagoNomina($request, $nomina);
        $this->sincronizarCambioPago($resultadoPago);

        return back()->with(
            'success',
            $resultadoPago['pagado']
                ? 'Nomina marcada como pagada. Prestamos y vacaciones aplicados al saldo del empleado.'
                : 'Nomina regresada a pendiente. Prestamos y vacaciones revertidos del saldo del empleado.'
        );
    }

    public function actualizarPagosMasivos(Request $request)
    {
        $validated = $request->validate([
            'fecha_corte' => 'nullable|date',
            'empleado_ids' => 'required|array|min:1',
            'empleado_ids.*' => 'integer|exists:empleados,id',
            'accion' => 'required|in:pagar,revertir',
        ]);

        [$inicioSemana, $finSemana] = $this->resolverSemanaNomina($request);
        $empleadoIds = collect($validated['empleado_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $forzarPagado = $validated['accion'] === 'pagar';
        $nominas = Nomina::whereIn('empleado_id', $empleadoIds)
            ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->orderBy('empleado_id')
            ->get();

        $procesadas = 0;
        $sinCambio = 0;
        $errores = 0;

        foreach ($nominas as $nomina) {
            try {
                $resultadoPago = $this->procesarCambioPagoNomina($request, $nomina, $forzarPagado);

                if ($resultadoPago['sin_cambio'] ?? false) {
                    $sinCambio++;
                    continue;
                }

                $this->sincronizarCambioPago($resultadoPago);
                $procesadas++;
            } catch (\Throwable $exception) {
                report($exception);
                $errores++;
            }
        }

        $sinRecibo = max(0, $empleadoIds->count() - $nominas->count());
        $estadoDestino = $forzarPagado ? 'liquidadas' : 'regresadas a pendiente';
        $estadoActual = $forzarPagado ? 'liquidadas' : 'pendientes';
        $partes = ["{$procesadas} nomina(s) {$estadoDestino}."];

        if ($sinCambio > 0) {
            $partes[] = "{$sinCambio} ya estaban {$estadoActual}.";
        }

        if ($sinRecibo > 0) {
            $partes[] = "{$sinRecibo} empleado(s) no tenian recibo generado para esta semana.";
        }

        if ($errores > 0) {
            $partes[] = "{$errores} no se pudieron procesar; revisa el log.";
        }

        return back()->with('success', implode(' ', $partes));
    }

    private function procesarCambioPagoNomina(Request $request, Nomina $nomina, ?bool $forzarPagado = null): array
    {
        return DB::transaction(function () use ($request, $nomina, $forzarPagado) {
            $nomina = Nomina::whereKey($nomina->id)->lockForUpdate()->firstOrFail();
            $empleado = Empleado::findOrFail($nomina->empleado_id);
            $controlPrestamoAplicado = $this->controlPrestamoAplicadoDisponible();
            [$prestamoOtorgadoAplicado, $prestamoDescuentoAplicado] = $this->prestamoAplicadoAnterior($nomina, $controlPrestamoAplicado);

            if ($forzarPagado !== null && (bool) $nomina->pagado === $forzarPagado) {
                return [
                    'pagado' => (bool) $nomina->pagado,
                    'empleado_id' => $empleado->id,
                    'nomina_id' => $nomina->id,
                    'sin_cambio' => true,
                ];
            }

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

                return [
                    'pagado' => false,
                    'empleado_id' => $empleado->id,
                    'nomina_id' => $nomina->id,
                ];
            }

            $inicioSemana = Carbon::parse($nomina->fecha_inicio)->startOfDay();
            $finSemana = Carbon::parse($nomina->fecha_fin)->endOfDay();
            $estadoCaptura = $this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana);

            if (!$estadoCaptura['lista_para_nomina']) {
                throw ValidationException::withMessages([
                    'asistencia' => $estadoCaptura['mensaje'],
                ]);
            }

            if ($this->requestTieneAjustesNomina($request)) {
                $ajustes = $this->ajustesDesdeRequest($request, $empleado);
                $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);

                $nomina->fill($this->datosNominaParaGuardar($inicioSemana, $finSemana, $desglose));
                $nomina->save();
                $nomina->refresh();
            } else {
                $ajustes = $this->ajustesDesdeNomina($nomina, $empleado);
                $desglose = $this->calcularDesgloseNomina($empleado, $inicioSemana, $finSemana, $ajustes);
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

            return [
                'pagado' => true,
                'empleado_id' => $empleado->id,
                'nomina_id' => $nomina->id,
                'desglose' => $desglose,
            ];
        });
    }

    private function sincronizarCambioPago(array $resultadoPago): void
    {
        if ($resultadoPago['sin_cambio'] ?? false) {
            return;
        }

        $empleadoSync = Empleado::find($resultadoPago['empleado_id']);
        $nominaSync = Nomina::find($resultadoPago['nomina_id']);

        if ($empleadoSync && $nominaSync) {
            if ($resultadoPago['pagado']) {
                FirebaseSyncService::sincronizarNominaPagada($empleadoSync, $nominaSync, $resultadoPago['desglose'] ?? []);
            } else {
                FirebaseSyncService::eliminarNominaPagada($empleadoSync, $nominaSync);
            }
        }
    }

    private function resolverSemanaNomina(Request $request): array
    {
        return SemanaNomina::desdeCorte($request->input('fecha_corte'));
    }

    private function empleadosBaseNomina(): Collection
    {
        return Empleado::query()
            ->select([
                'id',
                'numero_empleado',
                'numero_empleado_baja',
                'nombre_completo',
                'banco',
                'numero_cuenta',
                'saldo_prestamo',
                'cuota_prestamo',
                'sueldo_por_hora',
                'sueldo_semanal',
                'es_estudiante',
                'descuento_imss',
                'descuento_isr',
                'descuento_infonavit',
                'fecha_ingreso',
                'fecha_baja',
                'estatus',
            ])
            ->where('estatus', true)
            ->orderBy('banco')
            ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC")
            ->orderBy('nombre_completo')
            ->get();
    }

    private function empleadoNominaPayload(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'numero_empleado' => $empleado->numero_empleado,
            'numero_empleado_baja' => $empleado->numero_empleado_baja,
            'nombre_completo' => $empleado->nombre_completo,
            'banco' => $empleado->banco,
            'numero_cuenta' => $empleado->numero_cuenta,
            'saldo_prestamo' => (float) ($empleado->saldo_prestamo ?? 0),
            'cuota_prestamo' => (float) ($empleado->cuota_prestamo ?? 0),
            'sueldo_por_hora' => (float) ($empleado->sueldo_por_hora ?? 0),
            'sueldo_semanal' => (float) ($empleado->sueldo_semanal ?? 0),
            'es_estudiante' => (bool) ($empleado->es_estudiante ?? false),
            'descuento_imss' => (float) ($empleado->descuento_imss ?? 0),
            'descuento_isr' => (float) ($empleado->descuento_isr ?? 0),
            'descuento_infonavit' => (float) ($empleado->descuento_infonavit ?? 0),
            'fecha_ingreso' => $empleado->fecha_ingreso,
            'fecha_baja' => $empleado->fecha_baja,
            'estatus' => (bool) $empleado->estatus,
        ];
    }

    private function nominasPeriodoPorEmpleado(array $empleadoIds, Carbon $inicioSemana, Carbon $finSemana): Collection
    {
        if (count($empleadoIds) === 0) {
            return collect();
        }

        return Nomina::query()
            ->whereIn('empleado_id', $empleadoIds)
            ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->get()
            ->keyBy('empleado_id');
    }

    private function asistenciasPeriodoPorEmpleado(array $empleadoIds, Carbon $inicioSemana, Carbon $finSemana): Collection
    {
        if (count($empleadoIds) === 0) {
            return collect();
        }

        return Asistencia::query()
            ->whereIn('empleado_id', $empleadoIds)
            ->whereBetween('fecha', [
                $inicioSemana->format('Y-m-d'),
                $finSemana->format('Y-m-d'),
            ])
            ->get()
            ->groupBy('empleado_id');
    }

    private function asistenciasMiercolesAnteriorPorEmpleado(array $empleadoIds, Carbon $inicioSemana): Collection
    {
        if (count($empleadoIds) === 0) {
            return collect();
        }

        $miercolesAnterior = $inicioSemana->copy()->subDay();

        if (!$miercolesAnterior->isWednesday()) {
            return collect();
        }

        return Asistencia::query()
            ->whereIn('empleado_id', $empleadoIds)
            ->whereDate('fecha', $miercolesAnterior->format('Y-m-d'))
            ->where('tipo_asistencia', 'Normal')
            ->get()
            ->keyBy('empleado_id');
    }

    private function saldosHorasAdeudoPorEmpleado(array $empleadoIds, Carbon $inicioSemana): Collection
    {
        if (count($empleadoIds) === 0) {
            return collect();
        }

        return Nomina::query()
            ->whereIn('empleado_id', $empleadoIds)
            ->whereDate('fecha_inicio', '<', $inicioSemana->format('Y-m-d'))
            ->selectRaw('empleado_id, COALESCE(SUM(horas_adeudo_generadas), 0) - COALESCE(SUM(horas_adeudo_descontadas), 0) as saldo')
            ->groupBy('empleado_id')
            ->pluck('saldo', 'empleado_id')
            ->map(fn ($saldo) => max(0, round((float) $saldo, 2)));
    }

    private function historialNominas(Request $request): array
    {
        $busqueda = trim((string) $request->input('historial_busqueda', ''));
        $historial = Nomina::query()
            ->select([
                'id',
                'empleado_id',
                'numero_semana',
                'fecha_inicio',
                'fecha_fin',
                'pago_neto',
                'pagado',
            ])
            ->with(['empleado' => fn ($query) => $query->select([
                'id',
                'nombre_completo',
                'numero_empleado',
                'numero_empleado_baja',
            ])])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('numero_semana', 'like', "%{$busqueda}%")
                        ->orWhere('fecha_inicio', 'like', "%{$busqueda}%")
                        ->orWhere('fecha_fin', 'like', "%{$busqueda}%")
                        ->orWhereHas('empleado', function ($query) use ($busqueda) {
                            $query->where('nombre_completo', 'like', "%{$busqueda}%")
                                ->orWhere('numero_empleado', 'like', "%{$busqueda}%")
                                ->orWhere('numero_empleado_baja', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'historial_page')
            ->withQueryString();

        return [
            'data' => $historial->getCollection()->map(fn (Nomina $nomina) => [
                'id' => $nomina->id,
                'numero_semana' => $nomina->numero_semana,
                'fecha_inicio' => $nomina->fecha_inicio,
                'fecha_fin' => $nomina->fecha_fin,
                'pago_neto' => (float) ($nomina->pago_neto ?? 0),
                'pagado' => (bool) $nomina->pagado,
                'empleado' => $nomina->empleado ? [
                    'id' => $nomina->empleado->id,
                    'nombre_completo' => $nomina->empleado->nombre_completo,
                    'numero_empleado' => $nomina->empleado->numero_empleado,
                    'numero_empleado_baja' => $nomina->empleado->numero_empleado_baja,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $historial->currentPage(),
                'last_page' => $historial->lastPage(),
                'per_page' => $historial->perPage(),
                'total' => $historial->total(),
                'from' => $historial->firstItem(),
                'to' => $historial->lastItem(),
            ],
            'filtros' => [
                'historial_busqueda' => $busqueda,
            ],
        ];
    }

    private function recibosDiferenciaImssPeriodo(Carbon $inicioSemana, Carbon $finSemana): Collection
    {
        return Nomina::query()
            ->with('empleado')
            ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->where('deposito_imss', '>', 0)
            ->get()
            ->map(function (Nomina $nomina) {
                $diferencia = round((float) ($nomina->pago_neto ?? 0) - (float) ($nomina->deposito_imss ?? 0), 2);

                return [
                    'nomina' => $nomina,
                    'empleado' => $nomina->empleado,
                    'diferencia_imss' => $diferencia,
                ];
            })
            ->filter(fn (array $recibo) => $recibo['empleado'] && abs($recibo['diferencia_imss']) >= 0.01)
            ->sort(function (array $a, array $b) {
                $empleadoA = $a['empleado'];
                $empleadoB = $b['empleado'];

                return [
                    strtoupper((string) ($empleadoA?->banco ?? '')),
                    (int) ($empleadoA?->numero_empleado ?? $empleadoA?->numero_empleado_baja ?? 0),
                    strtoupper((string) ($empleadoA?->nombre_completo ?? '')),
                ] <=> [
                    strtoupper((string) ($empleadoB?->banco ?? '')),
                    (int) ($empleadoB?->numero_empleado ?? $empleadoB?->numero_empleado_baja ?? 0),
                    strtoupper((string) ($empleadoB?->nombre_completo ?? '')),
                ];
            })
            ->values();
    }

    private function abortarSiAsistenciaPendiente(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana): void
    {
        $estadoCaptura = $this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana);

        abort_if(!$estadoCaptura['lista_para_nomina'], 422, $estadoCaptura['mensaje']);
    }

    private function estadoCapturaAsistencia(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana, ?Collection $asistencias = null): array
    {
        $asistencias ??= Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [
                $inicioSemana->format('Y-m-d'),
                $finSemana->format('Y-m-d'),
            ])
            ->get();

        $fechasConRegistro = collect($asistencias
            ->map(fn (Asistencia $asistencia) => Carbon::parse($asistencia->fecha)->format('Y-m-d'))
            ->all())
            ->unique()
            ->values()
            ->all();

        return [
            'lista_para_nomina' => count($fechasConRegistro) > 0,
            'dias_requeridos' => $this->diasLaborablesNomina($empleado, $inicioSemana, $finSemana),
            'dias_capturados' => count($fechasConRegistro),
            'fechas_pendientes' => [],
            'sin_registros' => $asistencias->isEmpty(),
            'mensaje' => $asistencias->isEmpty()
                ? 'Asistencia pendiente de captura. Registra al menos un dia del periodo o importa el reloj para generar faltas.'
                : 'La asistencia del periodo esta lista para calcular nomina.',
        ];
    }

    private function diasLaborablesNomina(Empleado $empleado, Carbon $inicioSemana, Carbon $finSemana): int
    {
        $dias = 0;
        $cursor = $inicioSemana->copy()->startOfDay();
        $limite = $finSemana->copy()->startOfDay();
        $fechaIngreso = $empleado->fecha_ingreso ? Carbon::parse($empleado->fecha_ingreso)->startOfDay() : null;
        $fechaBaja = $empleado->fecha_baja ? Carbon::parse($empleado->fecha_baja)->startOfDay() : null;

        while ($cursor->lte($limite)) {
            if (!$cursor->isWeekend()
                && (!$fechaIngreso || $cursor->gte($fechaIngreso))
                && (!$fechaBaja || $cursor->lte($fechaBaja))) {
                $dias++;
            }

            $cursor->addDay();
        }

        return $dias;
    }

    private function calcularDesgloseNomina(
        Empleado $empleado,
        Carbon $inicioSemana,
        Carbon $finSemana,
        array $ajustes = [],
        ?Collection $asistenciasPrecargadas = null,
        ?Asistencia $asistenciaMiercolesAnterior = null,
        bool $miercolesAnteriorPrecargado = false
    ): array
    {
        $ajustes = array_merge($this->ajustesPorDefecto($empleado), $ajustes);
        $asistencias = $asistenciasPrecargadas ?? Asistencia::where('empleado_id', $empleado->id)
            ->whereBetween('fecha', [
                $inicioSemana->format('Y-m-d'),
                $finSemana->format('Y-m-d'),
            ])
            ->get();
        $estadoCaptura = $this->estadoCapturaAsistencia($empleado, $inicioSemana, $finSemana, $asistencias);

        if (!$estadoCaptura['lista_para_nomina']) {
            return $this->desglosePendienteCaptura($empleado, $estadoCaptura);
        }

        $horasNormales = (float) $asistencias->where('tipo_asistencia', 'Normal')->sum('horas_trabajadas');
        $horasExtraPeriodo = (float) $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->reject(fn ($asistencia) => $this->esMiercolesDeCorte($asistencia, $finSemana))
            ->sum(fn ($asistencia) => $this->horasExtraRedondeadas($asistencia));
        $horasExtraMiercolesAnterior = $this->horasExtraMiercolesAnterior(
            $empleado,
            $inicioSemana,
            $asistenciaMiercolesAnterior,
            $miercolesAnteriorPrecargado
        );
        $horasExtra = $horasExtraPeriodo + $horasExtraMiercolesAnterior;
        $horasAdeudoMiercolesAnterior = $this->horasAdeudoMiercolesAnterior(
            $empleado,
            $inicioSemana,
            $asistenciaMiercolesAnterior,
            $miercolesAnteriorPrecargado
        );
        $esEstudiante = $this->empleadoEsEstudiante($empleado);

        if (ReglasNominaEmpleado::sinHorasExtra($empleado)) {
            $horasExtraPeriodo = 0;
            $horasExtraMiercolesAnterior = 0;
            $horasExtra = 0;
        }

        if ($esEstudiante && $horasExtra > 0) {
            $horasNormales += $horasExtra;
            $horasExtraPeriodo = 0;
            $horasExtraMiercolesAnterior = 0;
            $horasExtra = 0;
        }

        $fechasFestivas = $this->fechasFestivasActivas($inicioSemana, $finSemana);
        $asistenciasFestivasTrabajadas = $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->filter(fn ($asistencia) => $this->esFechaFestiva($asistencia->fecha, $fechasFestivas)
                && $this->asistenciaTieneJornadaValida($asistencia))
            ->values();
        $fechasFestivasTrabajadas = $asistenciasFestivasTrabajadas
            ->map(fn ($asistencia) => Carbon::parse($asistencia->fecha)->format('Y-m-d'))
            ->unique()
            ->values();
        $diasFestivosTrabajados = $fechasFestivasTrabajadas->count();
        $horasFestivasTrabajadas = (float) $asistenciasFestivasTrabajadas->sum('horas_trabajadas');
        $diasFestivosLaborables = collect($fechasFestivas)
            ->filter(fn (string $fecha) => !Carbon::parse($fecha)->isWeekend()
                && (!$empleado->fecha_ingreso || Carbon::parse($fecha)->gte(Carbon::parse($empleado->fecha_ingreso)->startOfDay()))
                && (!$empleado->fecha_baja || Carbon::parse($fecha)->lte(Carbon::parse($empleado->fecha_baja)->startOfDay())))
            ->values();
        $diasFestivosNoTrabajados = $diasFestivosLaborables
            ->diff($fechasFestivasTrabajadas)
            ->count();
        $diasLaborablesRequeridos = max(0, (int) $estadoCaptura['dias_requeridos']);
        $fechasNormalesPagables = $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->filter(fn (Asistencia $asistencia) => $this->esDiaSueldoBasePagable($asistencia))
            ->map(fn (Asistencia $asistencia) => Carbon::parse($asistencia->fecha)->format('Y-m-d'))
            ->unique()
            ->values();
        $diasNormalesPagables = $fechasNormalesPagables->count();

        $diasFalta = $asistencias
            ->where('tipo_asistencia', 'Falta')
            ->filter(fn ($asistencia) => $this->esDiaFaltaDescontable($asistencia->fecha, $fechasFestivas))
            ->count();
        $faltasPagadas = min((int) $ajustes['faltas_pagadas'], $diasFalta);
        $faltasDescontables = max(0, $diasFalta - $faltasPagadas);
        $diasIncapacidadDetectadas = $asistencias->where('tipo_asistencia', 'Incapacidad')->count();
        $diasVacacionesDetectadas = $asistencias->where('tipo_asistencia', 'Vacaciones')->count();
        $faltasCubiertasVacaciones = min(max(0, (float) $ajustes['faltas_cubiertas_vacaciones']), $faltasDescontables);
        $faltasCubiertasIncapacidad = min(
            max(0, (float) $ajustes['faltas_cubiertas_incapacidad']),
            max(0, $faltasDescontables - $faltasCubiertasVacaciones)
        );
        $diasVacacionesAdicionales = max(0, (float) $ajustes['dias_vacaciones_adicionales']);
        $diasCubiertosPeriodo = min(
            $diasLaborablesRequeridos,
            $diasNormalesPagables
                + $diasFalta
                + $diasVacacionesDetectadas
                + $diasIncapacidadDetectadas
                + $diasFestivosNoTrabajados
        );
        $diasPendientesCaptura = max(0, $diasLaborablesRequeridos - $diasCubiertosPeriodo);
        $asistenciaCompletaPeriodo = $diasLaborablesRequeridos <= 0 || $diasPendientesCaptura <= 0;
        $usarTotalVacacionesLegacy = $ajustes['dias_vacaciones_pagadas'] !== null
            && $faltasCubiertasVacaciones <= 0
            && $diasVacacionesAdicionales <= 0;
        $diasVacacionesPagadas = $usarTotalVacacionesLegacy
            ? max(0, (float) $ajustes['dias_vacaciones_pagadas'])
            : $diasVacacionesDetectadas + $faltasCubiertasVacaciones + $diasVacacionesAdicionales;
        $diasVacacionesAdicionalesCalculadas = max(
            0,
            $diasVacacionesPagadas - $diasVacacionesDetectadas - $faltasCubiertasVacaciones
        );
        $diasIncapacidadPagadas = $diasIncapacidadDetectadas + $faltasCubiertasIncapacidad;
        $minutosTarde = (int) $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->filter(fn ($asistencia) => $this->esRetardoDescontable($asistencia, $fechasFestivas))
            ->sum('minutos_tarde');
        $minutosTardeDescontables = $minutosTarde >= self::UMBRAL_RETARDO_SEMANAL_MINUTOS
            ? $minutosTarde
            : 0;

        $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);
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
        $diasSueldoBasePagados = min($diasLaborablesRequeridos, $diasNormalesPagables + $faltasPagadas);
        $pagoBasePlanta = $asistenciaCompletaPeriodo
            ? $sueldoSemanal
            : $pagoDiaPlanta * $diasSueldoBasePagados;

        $horasAdeudoGeneradas = ($faltasPagadas * self::HORAS_FALTA_COMPLETA) + $horasAdeudoMiercolesAnterior;
        $horasAdeudoDescontadas = min((float) $ajustes['horas_adeudo_descontadas'], $horasExtra);
        $horasExtraPagadas = max(0, $horasExtra - $horasAdeudoDescontadas);
        $horasFestivasPagadas = (!$esEstudiante && $pagoPorHoraTopado)
            ? $diasFestivosNoTrabajados * self::HORAS_FALTA_COMPLETA
            : 0;

        if ($pagoPorHoraTopado) {
            $horasExtraParaTope = $horasExtraPagadas;
            $horasParaPagoPorHora = $horasNormales + $horasExtraParaTope + ($faltasPagadas * self::HORAS_FALTA_COMPLETA) + $horasFestivasPagadas;
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
                : $pagoBasePlanta;
            $pagoExtra = $horasExtraPagadas * ($tarifaBaseHora * 2);
        }
        $pagoIncapacidad = (!$esEstudiante && $diasIncapacidadPagadas > 0)
            ? $pagoDiaPlanta * 0.60 * $diasIncapacidadPagadas
            : 0;
        $pagoVacaciones = (!$esEstudiante && $diasVacacionesPagadas > 0)
            ? $pagoDiaPlanta * 1.25 * $diasVacacionesPagadas
            : 0;
        $pagoFestivoTrabajado = $this->pagoFestivoTrabajado(
            $esEstudiante,
            $pagoPorHoraTopado,
            $diasFestivosTrabajados,
            $horasFestivasTrabajadas,
            $pagoDiaPlanta,
            $sueldoPorHora
        );
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

        $totalPercepciones = $pagoNormal + $pagoExtra + $pagoIncapacidad + $pagoVacaciones + $pagoFestivoTrabajado + $prestamoOtorgado;
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
            'dias_festivos_trabajados' => round($diasFestivosTrabajados, 2),
            'horas_festivas_trabajadas' => round($horasFestivasTrabajadas, 2),
            'dias_festivos_no_trabajados' => round($diasFestivosNoTrabajados, 2),
            'pago_festivo_trabajado' => round($pagoFestivoTrabajado, 2),
            'dias_sueldo_base_pagados' => round($diasSueldoBasePagados, 2),
            'horas_adeudo_generadas' => round($horasAdeudoGeneradas, 2),
            'horas_adeudo_miercoles_anterior' => round($horasAdeudoMiercolesAnterior, 2),
            'horas_adeudo_descontadas' => round($horasAdeudoDescontadas, 2),
            'dias_falta' => $diasFalta,
            'dias_falta_pagados' => $faltasPagadas,
            'dias_falta_descontables' => $faltasDescontables,
            'dias_incapacidad' => round($diasIncapacidadPagadas, 2),
            'dias_incapacidad_detectadas' => $diasIncapacidadDetectadas,
            'dias_incapacidad_pagadas' => round($diasIncapacidadPagadas, 2),
            'faltas_cubiertas_vacaciones' => round($faltasCubiertasVacaciones, 2),
            'faltas_cubiertas_incapacidad' => round($faltasCubiertasIncapacidad, 2),
            'dias_vacaciones' => round($diasVacacionesPagadas, 2),
            'dias_vacaciones_detectadas' => $diasVacacionesDetectadas,
            'dias_vacaciones_adicionales' => round($diasVacacionesAdicionalesCalculadas, 2),
            'dias_vacaciones_pagadas' => round($diasVacacionesPagadas, 2),
            'minutos_tarde_acumulados' => $minutosTarde,
            'minutos_tarde_descontables' => $minutosTardeDescontables,
            'pago_normal' => round($pagoNormal, 2),
            'pago_extra' => round($pagoExtra, 2),
            'pago_incapacidad' => round($pagoIncapacidad, 2),
            'pago_vacaciones' => round($pagoVacaciones, 2),
            'pago_festivo' => round($pagoFestivoTrabajado, 2),
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
            'asistencia_lista' => true,
            'asistencia_pendiente_captura' => false,
            'dias_requeridos_asistencia' => $estadoCaptura['dias_requeridos'],
            'dias_capturados_asistencia' => $estadoCaptura['dias_capturados'],
            'dias_pendientes_captura' => $diasPendientesCaptura,
            'fechas_pendientes_captura' => [],
            'mensaje_captura_asistencia' => null,
        ];
    }

    private function desglosePendienteCaptura(Empleado $empleado, array $estadoCaptura): array
    {
        $esEstudiante = $this->empleadoEsEstudiante($empleado);
        $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);
        $sueldoSemanal = (float) ($empleado->sueldo_semanal ?? 0);

        if (!$esEstudiante && $sueldoSemanal <= 0 && $sueldoPorHora > 0) {
            $sueldoSemanal = $sueldoPorHora * self::HORAS_BASE_SEMANA;
        }

        return [
            'es_estudiante' => $esEstudiante,
            'pago_por_hora_topado' => ReglasNominaEmpleado::pagoPorHoraTopado($empleado),
            'sueldo_semanal' => round($sueldoSemanal, 2),
            'sueldo_por_hora' => round($sueldoPorHora, 2),
            'tarifa_base_hora' => 0,
            'pago_dia_planta' => 0,
            'horas_normales' => 0,
            'horas_extra' => 0,
            'horas_extra_periodo' => 0,
            'horas_extra_miercoles_anterior' => 0,
            'horas_extra_pagadas' => 0,
            'dias_festivos_trabajados' => 0,
            'horas_festivas_trabajadas' => 0,
            'dias_festivos_no_trabajados' => 0,
            'pago_festivo_trabajado' => 0,
            'horas_adeudo_generadas' => 0,
            'horas_adeudo_miercoles_anterior' => 0,
            'horas_adeudo_descontadas' => 0,
            'dias_falta' => 0,
            'dias_falta_pagados' => 0,
            'dias_falta_descontables' => 0,
            'dias_incapacidad' => 0,
            'dias_incapacidad_detectadas' => 0,
            'dias_incapacidad_pagadas' => 0,
            'faltas_cubiertas_vacaciones' => 0,
            'faltas_cubiertas_incapacidad' => 0,
            'dias_vacaciones' => 0,
            'dias_vacaciones_detectadas' => 0,
            'dias_vacaciones_adicionales' => 0,
            'dias_vacaciones_pagadas' => 0,
            'minutos_tarde_acumulados' => 0,
            'minutos_tarde_descontables' => 0,
            'pago_normal' => 0,
            'pago_extra' => 0,
            'pago_incapacidad' => 0,
            'pago_vacaciones' => 0,
            'pago_festivo' => 0,
            'prestamo_otorgado' => 0,
            'descuento_faltas' => 0,
            'descuento_retardos' => 0,
            'prestamo_descuento' => 0,
            'deduccion_prestamo' => 0,
            'deduccion_manual' => 0,
            'descuento_imss' => 0,
            'descuento_isr' => 0,
            'descuento_infonavit' => 0,
            'total_percepciones' => 0,
            'total_deducciones' => 0,
            'pago_neto' => 0,
            'asistencia_lista' => false,
            'asistencia_pendiente_captura' => true,
            'dias_requeridos_asistencia' => $estadoCaptura['dias_requeridos'],
            'dias_capturados_asistencia' => $estadoCaptura['dias_capturados'],
            'dias_pendientes_captura' => count($estadoCaptura['fechas_pendientes']),
            'fechas_pendientes_captura' => $estadoCaptura['fechas_pendientes'],
            'mensaje_captura_asistencia' => $estadoCaptura['mensaje'],
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
        if (!($desglose['asistencia_lista'] ?? true)) {
            throw ValidationException::withMessages([
                'asistencia' => $desglose['mensaje_captura_asistencia'] ?? 'Asistencia pendiente de captura.',
            ]);
        }

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

    private function horasExtraMiercolesAnterior(
        Empleado $empleado,
        Carbon $inicioSemana,
        ?Asistencia $asistenciaPrecargada = null,
        bool $precargada = false
    ): float
    {
        $miercolesAnterior = $inicioSemana->copy()->subDay();

        if (!$miercolesAnterior->isWednesday()) {
            return 0;
        }

        $asistencia = $precargada
            ? $asistenciaPrecargada
            : Asistencia::where('empleado_id', $empleado->id)
                ->whereDate('fecha', $miercolesAnterior->format('Y-m-d'))
                ->where('tipo_asistencia', 'Normal')
                ->first();

        return $asistencia ? $this->horasExtraRedondeadas($asistencia) : 0;
    }

    private function horasAdeudoMiercolesAnterior(
        Empleado $empleado,
        Carbon $inicioSemana,
        ?Asistencia $asistenciaPrecargada = null,
        bool $precargada = false
    ): float
    {
        $miercolesAnterior = $inicioSemana->copy()->subDay();

        if (!$miercolesAnterior->isWednesday()) {
            return 0;
        }

        $asistencia = $precargada
            ? $asistenciaPrecargada
            : Asistencia::where('empleado_id', $empleado->id)
                ->whereDate('fecha', $miercolesAnterior->format('Y-m-d'))
                ->where('tipo_asistencia', 'Normal')
                ->first();

        if (!$asistencia || !$asistencia->hora_salida) {
            return 0;
        }

        $fechaBase = $miercolesAnterior->format('Y-m-d');
        $salida = Carbon::parse($fechaBase . ' ' . $asistencia->hora_salida);
        $limiteNormal = Carbon::parse($fechaBase . ' 17:30:00');

        if (!$salida->lessThan($limiteNormal)) {
            return 0;
        }

        return round($salida->diffInMinutes($limiteNormal) / 60, 2);
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

    private function fechasFestivasActivas(Carbon $inicio, Carbon $fin): array
    {
        if (!Schema::hasTable('dias_festivos')) {
            return [];
        }

        return DiaFestivo::where('activo', true)
            ->whereDate('fecha', '>=', $inicio->format('Y-m-d'))
            ->whereDate('fecha', '<=', $fin->format('Y-m-d'))
            ->pluck('fecha')
            ->map(fn ($fecha) => Carbon::parse($fecha)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();
    }

    private function esFechaFestiva($fecha, array $fechasFestivas): bool
    {
        return in_array(Carbon::parse($fecha)->format('Y-m-d'), $fechasFestivas, true);
    }

    private function esDiaFaltaDescontable($fecha, array $fechasFestivas = []): bool
    {
        return !Carbon::parse($fecha)->isWeekend()
            && !$this->esFechaFestiva($fecha, $fechasFestivas);
    }

    private function esDiaSueldoBasePagable(Asistencia $asistencia): bool
    {
        return !Carbon::parse($asistencia->fecha)->isWeekend()
            && $this->asistenciaTieneJornadaValida($asistencia);
    }

    private function esRetardoDescontable(Asistencia $asistencia, array $fechasFestivas = []): bool
    {
        if (Carbon::parse($asistencia->fecha)->isWeekend()
            || $this->esFechaFestiva($asistencia->fecha, $fechasFestivas)
            || !$this->asistenciaTieneJornadaValida($asistencia)) {
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

    private function asistenciaTieneJornadaValida(Asistencia $asistencia): bool
    {
        if (!$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return false;
        }

        $fechaBase = Carbon::parse($asistencia->fecha)->format('Y-m-d');
        $entrada = Carbon::parse($fechaBase . ' ' . $asistencia->hora_entrada);
        $salida = Carbon::parse($fechaBase . ' ' . $asistencia->hora_salida);

        return $salida->greaterThan($entrada);
    }

    private function pagoFestivoTrabajado(
        bool $esEstudiante,
        bool $pagoPorHoraTopado,
        float $diasFestivosTrabajados,
        float $horasFestivasTrabajadas,
        float $pagoDiaPlanta,
        float $sueldoPorHora
    ): float {
        if ($diasFestivosTrabajados <= 0) {
            return 0;
        }

        if ($esEstudiante || $pagoPorHoraTopado) {
            return $horasFestivasTrabajadas * $sueldoPorHora;
        }

        return $diasFestivosTrabajados * $pagoDiaPlanta;
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
        foreach ([
            'prestamo_otorgado',
            'prestamo_descuento',
            'deduccion_manual',
            'faltas_pagadas',
            'faltas_cubiertas_vacaciones',
            'faltas_cubiertas_incapacidad',
            'horas_adeudo_descontadas',
            'dias_vacaciones_adicionales',
            'dias_vacaciones_pagadas',
        ] as $campo) {
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
            'faltas_cubiertas_vacaciones' => 'nullable|numeric|min:0',
            'faltas_cubiertas_incapacidad' => 'nullable|numeric|min:0',
            'horas_adeudo_descontadas' => 'nullable|numeric|min:0',
            'dias_vacaciones_adicionales' => 'nullable|numeric|min:0',
            'dias_vacaciones_pagadas' => 'nullable|numeric|min:0',
        ]);

        $default = $this->ajustesPorDefecto($empleado);

        return [
            'prestamo_otorgado' => (float) ($validated['prestamo_otorgado'] ?? $default['prestamo_otorgado']),
            'prestamo_descuento' => (float) ($validated['prestamo_descuento'] ?? $default['prestamo_descuento']),
            'deduccion_manual' => (float) ($validated['deduccion_manual'] ?? $default['deduccion_manual']),
            'faltas_pagadas' => (int) ($validated['faltas_pagadas'] ?? $default['faltas_pagadas']),
            'faltas_cubiertas_vacaciones' => (float) ($validated['faltas_cubiertas_vacaciones'] ?? $default['faltas_cubiertas_vacaciones']),
            'faltas_cubiertas_incapacidad' => (float) ($validated['faltas_cubiertas_incapacidad'] ?? $default['faltas_cubiertas_incapacidad']),
            'horas_adeudo_descontadas' => (float) ($validated['horas_adeudo_descontadas'] ?? $default['horas_adeudo_descontadas']),
            'dias_vacaciones_adicionales' => (float) ($validated['dias_vacaciones_adicionales'] ?? $default['dias_vacaciones_adicionales']),
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
            'faltas_cubiertas_vacaciones' => (float) ($nomina->faltas_cubiertas_vacaciones ?? 0),
            'faltas_cubiertas_incapacidad' => (float) ($nomina->faltas_cubiertas_incapacidad ?? 0),
            'horas_adeudo_descontadas' => (float) ($nomina->horas_adeudo_descontadas ?? 0),
            'dias_vacaciones_adicionales' => (float) ($nomina->dias_vacaciones_adicionales ?? 0),
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
            'faltas_cubiertas_vacaciones' => 0,
            'faltas_cubiertas_incapacidad' => 0,
            'horas_adeudo_descontadas' => 0,
            'dias_vacaciones_adicionales' => 0,
            'dias_vacaciones_pagadas' => null,
        ];
    }

    private function resumenAjustesNomina(
        Empleado $empleado,
        ?Nomina $nomina,
        array $desglose,
        Carbon $inicioSemana,
        ?float $saldoHorasAdeudoPrecargado = null
    ): array
    {
        $saldoAnterior = $saldoHorasAdeudoPrecargado ?? $this->saldoHorasAdeudo($empleado->id, $inicioSemana);
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
            'faltas_cubiertas_vacaciones' => $desglose['faltas_cubiertas_vacaciones'],
            'faltas_cubiertas_incapacidad' => $desglose['faltas_cubiertas_incapacidad'],
            'dias_incapacidad_detectadas' => $desglose['dias_incapacidad_detectadas'],
            'dias_incapacidad_pagadas' => $desglose['dias_incapacidad_pagadas'],
            'dias_vacaciones_detectadas' => $desglose['dias_vacaciones_detectadas'],
            'dias_vacaciones_adicionales' => $desglose['dias_vacaciones_adicionales'],
            'dias_vacaciones_pagadas' => $desglose['dias_vacaciones_pagadas'],
            'pago_incapacidad' => $desglose['pago_incapacidad'],
            'pago_vacaciones' => $desglose['pago_vacaciones'],
            'pago_dia_planta' => $desglose['pago_dia_planta'],
            'horas_extra_detectadas' => $desglose['horas_extra'],
            'horas_extra_periodo' => $desglose['horas_extra_periodo'],
            'horas_extra_miercoles_anterior' => $desglose['horas_extra_miercoles_anterior'],
            'horas_extra_pagadas' => $desglose['horas_extra_pagadas'],
            'dias_festivos_trabajados' => $desglose['dias_festivos_trabajados'],
            'horas_festivas_trabajadas' => $desglose['horas_festivas_trabajadas'],
            'dias_festivos_no_trabajados' => $desglose['dias_festivos_no_trabajados'],
            'pago_festivo_trabajado' => $desglose['pago_festivo_trabajado'],
            'horas_adeudo_descontadas' => $desglose['horas_adeudo_descontadas'],
            'horas_adeudo_generadas' => $desglose['horas_adeudo_generadas'],
            'horas_adeudo_miercoles_anterior' => $desglose['horas_adeudo_miercoles_anterior'],
            'saldo_horas_adeudo_anterior' => round($saldoAnterior, 2),
            'saldo_horas_adeudo_final' => round($saldoFinal, 2),
            'pago_por_hora_topado' => $desglose['pago_por_hora_topado'],
            'minutos_tarde' => $desglose['minutos_tarde_acumulados'],
            'minutos_tarde_descontables' => $desglose['minutos_tarde_descontables'],
            'total_percepciones' => $desglose['total_percepciones'],
            'total_deducciones' => $desglose['total_deducciones'],
            'pago_neto' => $desglose['pago_neto'],
            'deposito_imss' => round((float) ($nomina->deposito_imss ?? 0), 2),
            'diferencia_imss' => $nomina && (float) ($nomina->deposito_imss ?? 0) > 0
                ? round($desglose['pago_neto'] - (float) $nomina->deposito_imss, 2)
                : 0,
            'suma_total_depositos_imss' => $nomina && (float) ($nomina->deposito_imss ?? 0) > 0
                ? round((float) $nomina->deposito_imss + ($desglose['pago_neto'] - (float) $nomina->deposito_imss), 2)
                : 0,
            'asistencia_lista' => $desglose['asistencia_lista'],
            'asistencia_pendiente_captura' => $desglose['asistencia_pendiente_captura'],
            'dias_requeridos_asistencia' => $desglose['dias_requeridos_asistencia'],
            'dias_capturados_asistencia' => $desglose['dias_capturados_asistencia'],
            'dias_pendientes_captura' => $desglose['dias_pendientes_captura'],
            'fechas_pendientes_captura' => $desglose['fechas_pendientes_captura'],
            'mensaje_captura_asistencia' => $desglose['mensaje_captura_asistencia'],
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
        $datos = [
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

        foreach ([
            'faltas_cubiertas_vacaciones',
            'faltas_cubiertas_incapacidad',
            'dias_vacaciones_adicionales',
            'dias_festivos_trabajados',
            'horas_festivas_trabajadas',
            'pago_festivo_trabajado',
        ] as $campo) {
            if (Schema::hasColumn('nominas', $campo)) {
                $datos[$campo] = $desglose[$campo];
            }
        }

        return $datos;
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
