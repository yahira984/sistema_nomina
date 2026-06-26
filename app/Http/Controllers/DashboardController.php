<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Empleado;
use App\Models\Asistencia;
use App\Models\DiaFestivo;
use App\Models\Nomina;
use App\Services\DiasFestivosMexicoService;
use App\Support\ReglasNominaEmpleado;
use App\Support\SemanaNomina;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const UMBRAL_RETARDO_SEMANAL_MINUTOS = 30;

    public function index()
    {
        $hoy = Carbon::now();
        $mesActual = $hoy->month;
        $anioActual = $hoy->year;

        // 1. LÓGICA DE SEMANAS
        [$inicioSemana, $finSemana, $semanaActual] = SemanaNomina::desdeCorte();
        $diasFestivosDashboard = ['mes' => [], 'proximos' => []];

        if (Schema::hasTable('dias_festivos')) {
            app(DiasFestivosMexicoService::class)->sincronizarRango($anioActual, $anioActual + 1);
            $diasFestivosDashboard = [
                'mes' => $this->diasFestivosDelMes($hoy),
                'proximos' => $this->proximosDiasFestivos($hoy),
            ];
        }

        // 2. INDICADORES RÁPIDOS
        $totalEmpleados = Empleado::where('estatus', true)->count();
        $gastoSemanal = Nomina::whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->where('pagado', true)
            ->sum('pago_neto');
        // Buscamos las nóminas de la semana actual donde la columna booleana 'pagado' sea falsa (0)
        $nominasPendientes = Nomina::whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->where('pagado', false)
            ->count();
        $faltasMes = $this->asistenciasDashboard()
            ->where('tipo_asistencia', 'Falta')
            ->whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->get(['fecha'])
            ->filter(fn ($asistencia) => !$this->esFinDeSemana($asistencia->fecha))
            ->count();

        // 3. CUMPLEAÑOS DEL MES
        $cumpleaneros = Empleado::where('estatus', true)
            ->whereNotNull('fecha_nacimiento')
            ->get(['id', 'numero_empleado', 'nombre_completo', 'fecha_nacimiento'])
            ->filter(function ($empleado) use ($mesActual) {
                return Carbon::parse($empleado->fecha_nacimiento)->month === $mesActual;
            })
            ->map(function ($empleado) use ($hoy) {
                $fechaNacimiento = Carbon::parse($empleado->fecha_nacimiento);

                return [
                    'id' => $empleado->id,
                    'numero_empleado' => $empleado->numero_empleado,
                    'nombre_completo' => $empleado->nombre_completo,
                    'fecha_nacimiento' => $fechaNacimiento->format('Y-m-d'),
                    'dia' => (int) $fechaNacimiento->day,
                    'fecha_formateada' => $fechaNacimiento->format('d/m'),
                    'edad' => $fechaNacimiento->age,
                    'es_hoy' => $fechaNacimiento->month === $hoy->month && $fechaNacimiento->day === $hoy->day,
                ];
            })
            ->sortBy(fn ($empleado) => str_pad((string) $empleado['dia'], 2, '0', STR_PAD_LEFT).$empleado['nombre_completo'])
            ->values();

        // 4. DATOS GRÁFICA DE PASTEL (Estatus)
        $graficaAsistencia = [
            $this->asistenciasDashboard()->whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Normal')->count(),
            $faltasMes,
            $this->asistenciasDashboard()->whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Vacaciones')->count(),
            $this->asistenciasDashboard()->whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Incapacidad')->count(),
        ];

        // 5. DATOS GRÁFICA DE BARRAS (Horas Extra últimos 7 días)
        $ultimos7Dias = collect();
        for ($i = 6; $i >= 0; $i--) {
            $fecha = $hoy->copy()->subDays($i)->format('Y-m-d');
            $horas = $this->asistenciasDashboard()->where('fecha', $fecha)->sum('horas_extra');
            $ultimos7Dias->push([
                'fecha' => Carbon::parse($fecha)->format('d M'),
                'horas' => round($horas, 2)
            ]);
        }

        return Inertia::render('Dashboard', [
            'totalEmpleados' => $totalEmpleados,
            'semanaContable' => $semanaActual,
            'gastoSemanal' => number_format($gastoSemanal, 2, '.', ''),
            'corteSemana' => 'Jueves a miércoles',
            'nominasPendientes' => $nominasPendientes,
            'kpis' => [
                'faltas' => $faltasMes,
                'cumpleaneros' => $cumpleaneros
            ],
            'graficaAsistencia' => $graficaAsistencia,
            'graficaExtras' => [
                'categorias' => $ultimos7Dias->pluck('fecha'),
                'datos' => $ultimos7Dias->pluck('horas')
            ],
            'retardosControl' => [
                'semana' => $this->liderRetardos($inicioSemana, $finSemana, 'Semana ' . $semanaActual),
                'mes' => $this->liderRetardos($hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth(), $hoy->locale('es')->isoFormat('MMMM YYYY')),
                'anio' => $this->liderRetardos($hoy->copy()->startOfYear(), $hoy->copy()->endOfYear(), (string) $anioActual),
            ],
            'tempranoControl' => [
                'semana' => $this->liderLlegadasTempranas($inicioSemana, $finSemana, 'Semana ' . $semanaActual),
                'mes' => $this->liderLlegadasTempranas($hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth(), $hoy->locale('es')->isoFormat('MMMM YYYY')),
                'anio' => $this->liderLlegadasTempranas($hoy->copy()->startOfYear(), $hoy->copy()->endOfYear(), (string) $anioActual),
            ],
            'finanzasNomina' => [
                'desgloseGasto' => $this->desgloseGastoSemanal($inicioSemana, $finSemana),
                'prestamos' => $this->resumenPrestamos($hoy),
                'comparativa' => $this->comparativaGastoNomina($inicioSemana, $finSemana),
            ],
            'operatividad' => [
                'mapaCalor' => $this->mapaCalorAusentismo($hoy),
                'puntualidad' => $this->tasaPuntualidadGlobal($inicioSemana, $finSemana),
            ],
            'recursosHumanos' => [
                'rotacion' => $this->rotacionMes($hoy),
                'pasivoVacacional' => $this->pasivoVacacional(),
                'antiguedad' => $this->distribucionAntiguedad(),
            ],
            'diasFestivos' => $diasFestivosDashboard,
            'avanceLaboralAnual' => $this->avanceLaboralAnual($hoy),
        ]);
    }

    private function avanceLaboralAnual(Carbon $hoy): array
    {
        $inicioAnio = $hoy->copy()->startOfYear();
        $finAnio = $hoy->copy()->endOfYear();
        $hoyCorte = $hoy->copy()->startOfDay();
        $festivosLaborables = collect();

        if (Schema::hasTable('dias_festivos')) {
            $festivosLaborables = DiaFestivo::where('activo', true)
                ->whereBetween('fecha', [$inicioAnio->format('Y-m-d'), $finAnio->format('Y-m-d')])
                ->orderBy('fecha')
                ->get()
                ->filter(fn ($dia) => !Carbon::parse($dia->fecha)->isSunday())
                ->values();
        }

        $fechasFestivas = $festivosLaborables
            ->map(fn ($dia) => Carbon::parse($dia->fecha)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $diasCalendario = (int) $inicioAnio->diffInDays($finAnio) + 1;
        $domingos = $this->contarDiasPeriodo($inicioAnio, $finAnio, fn (Carbon $fecha) => $fecha->isSunday());
        $diasLaborables = $this->contarDiasLaborablesCalendario($inicioAnio, $finAnio, $fechasFestivas);
        $finTranscurrido = $hoyCorte->gt($finAnio) ? $finAnio : $hoyCorte;
        $diasTranscurridos = $this->contarDiasLaborablesCalendario($inicioAnio, $finTranscurrido, $fechasFestivas);
        $diasPendientes = max(0, $diasLaborables - $diasTranscurridos);

        return [
            'anio' => (int) $hoy->year,
            'inicio' => $inicioAnio->format('Y-m-d'),
            'fin' => $finAnio->format('Y-m-d'),
            'dias_calendario' => $diasCalendario,
            'domingos' => $domingos,
            'festivos_descontados' => count($fechasFestivas),
            'dias_laborables' => $diasLaborables,
            'dias_transcurridos' => $diasTranscurridos,
            'dias_pendientes' => $diasPendientes,
            'porcentaje' => $diasLaborables > 0 ? round(($diasTranscurridos / $diasLaborables) * 100, 1) : 0,
            'festivos' => $festivosLaborables
                ->map(fn ($dia) => $this->mapearDiaFestivo($dia))
                ->values()
                ->all(),
        ];
    }

    private function contarDiasLaborablesCalendario(Carbon $inicio, Carbon $fin, array $fechasFestivas): int
    {
        return $this->contarDiasPeriodo($inicio, $fin, function (Carbon $fecha) use ($fechasFestivas) {
            return !$fecha->isSunday() && !in_array($fecha->format('Y-m-d'), $fechasFestivas, true);
        });
    }

    private function contarDiasPeriodo(Carbon $inicio, Carbon $fin, callable $filtro): int
    {
        if ($fin->lt($inicio)) {
            return 0;
        }

        $cursor = $inicio->copy()->startOfDay();
        $limite = $fin->copy()->startOfDay();
        $total = 0;

        while ($cursor->lte($limite)) {
            if ($filtro($cursor->copy())) {
                $total++;
            }

            $cursor->addDay();
        }

        return $total;
    }

    private function diasFestivosDelMes(Carbon $hoy): array
    {
        return DiaFestivo::where('activo', true)
            ->whereBetween('fecha', [$hoy->copy()->startOfMonth()->format('Y-m-d'), $hoy->copy()->endOfMonth()->format('Y-m-d')])
            ->orderBy('fecha')
            ->get()
            ->map(fn ($dia) => $this->mapearDiaFestivo($dia))
            ->values()
            ->all();
    }

    private function proximosDiasFestivos(Carbon $hoy): array
    {
        return DiaFestivo::where('activo', true)
            ->whereDate('fecha', '>=', $hoy->copy()->startOfDay()->format('Y-m-d'))
            ->orderBy('fecha')
            ->limit(5)
            ->get()
            ->map(fn ($dia) => $this->mapearDiaFestivo($dia))
            ->values()
            ->all();
    }

    private function mapearDiaFestivo(DiaFestivo $dia): array
    {
        $fecha = Carbon::parse($dia->fecha)->startOfDay();
        $hoy = now()->startOfDay();

        return [
            'id' => $dia->id,
            'fecha' => $fecha->format('Y-m-d'),
            'nombre' => $dia->nombre,
            'tipo' => $dia->tipo,
            'es_oficial' => (bool) $dia->es_oficial,
            'origen' => $dia->origen,
            'descripcion' => $dia->descripcion,
            'fecha_corta' => $fecha->locale('es')->isoFormat('D MMM'),
            'dia_semana' => $fecha->locale('es')->isoFormat('dddd'),
            'dias_restantes' => (int) $hoy->diffInDays($fecha, false),
        ];
    }

    private function desgloseGastoSemanal(Carbon $inicioSemana, Carbon $finSemana): array
    {
        $nominas = Nomina::with('empleado')
            ->whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
            ->where('pagado', true)
            ->get();

        $desglose = [
            'Salario base' => 0,
            'Horas extra' => 0,
            'Primas vacacionales' => 0,
            'Incapacidades' => 0,
        ];
        $totalNetoPagado = 0;

        foreach ($nominas as $nomina) {
            $empleado = $nomina->empleado;
            if (!$empleado) {
                continue;
            }
            $totalNetoPagado += (float) ($nomina->pago_neto ?? 0);

            $esEstudiante = (bool) ($empleado->es_estudiante ?? false);
            $sueldoSemanal = (float) ($empleado->sueldo_semanal ?? 0);
            $sueldoPorHora = (float) ($empleado->sueldo_por_hora ?? 0);

            if (!$esEstudiante && $sueldoSemanal <= 0 && $sueldoPorHora > 0) {
                $sueldoSemanal = $sueldoPorHora * 56;
            }

            $tarifaHora = $esEstudiante
                ? $sueldoPorHora
                : ($sueldoSemanal > 0 ? $sueldoSemanal / 56 : 0);
            $pagoDia = $sueldoSemanal > 0 ? $sueldoSemanal / 7 : 0;

            $pagoExtra = (float) ($nomina->horas_extra_pagadas ?? $nomina->horas_extra ?? 0) * ($tarifaHora * 2);
            $pagoVacaciones = !$esEstudiante
                ? (float) ($nomina->dias_vacaciones_pagadas ?? 0) * ($pagoDia * 1.25)
                : 0;
            $diasIncapacidad = (float) ($nomina->faltas_cubiertas_incapacidad ?? 0)
                + $this->diasIncapacidadPeriodo($nomina);
            $pagoIncapacidad = !$esEstudiante ? $diasIncapacidad * ($pagoDia * 0.60) : 0;
            $totalPercepcionesNomina = max(0, (float) ($nomina->total_percepciones ?? 0) - (float) ($nomina->prestamo_otorgado ?? 0));
            $salarioBase = max(0, $totalPercepcionesNomina - $pagoExtra - $pagoVacaciones - $pagoIncapacidad);

            $desglose['Salario base'] += $salarioBase;
            $desglose['Horas extra'] += $pagoExtra;
            $desglose['Primas vacacionales'] += $pagoVacaciones;
            $desglose['Incapacidades'] += $pagoIncapacidad;
        }

        $totalBruto = array_sum($desglose);

        if ($totalBruto > 0) {
            $factorNeto = $totalNetoPagado / $totalBruto;
            foreach ($desglose as $concepto => $valor) {
                $desglose[$concepto] = $valor * $factorNeto;
            }
        } elseif ($totalNetoPagado > 0) {
            $desglose['Salario base'] = $totalNetoPagado;
        }

        $total = array_sum($desglose);

        return [
            'labels' => array_keys($desglose),
            'datos' => array_map(fn ($valor) => round($valor, 2), array_values($desglose)),
            'porcentajes' => array_map(fn ($valor) => $total > 0 ? round(($valor / $total) * 100, 1) : 0, array_values($desglose)),
            'total' => round($total, 2),
        ];
    }

    private function diasIncapacidadPeriodo(Nomina $nomina): int
    {
        return Asistencia::where('empleado_id', $nomina->empleado_id)
            ->whereBetween('fecha', [$nomina->fecha_inicio, $nomina->fecha_fin])
            ->where('tipo_asistencia', 'Incapacidad')
            ->count();
    }

    private function resumenPrestamos(Carbon $hoy): array
    {
        $inicioMes = $hoy->copy()->startOfMonth()->format('Y-m-d');
        $finMes = $hoy->copy()->endOfMonth()->format('Y-m-d');
        $capitalPrestado = (float) Empleado::where('saldo_prestamo', '>', 0)->sum('saldo_prestamo');

        if (Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_at')) {
            $recuperadoMes = (float) Nomina::where('pagado', true)
                ->whereBetween('prestamo_saldo_aplicado_at', [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()])
                ->sum('prestamo_saldo_aplicado_descuento');
        } else {
            $recuperadoMes = (float) Nomina::where('pagado', true)
                ->whereBetween('fecha_fin', [$inicioMes, $finMes])
                ->sum('prestamo_descuento');
        }

        return [
            'capitalPrestado' => round($capitalPrestado, 2),
            'recuperadoMes' => round($recuperadoMes, 2),
        ];
    }

    private function comparativaGastoNomina(Carbon $inicioSemana, Carbon $finSemana): array
    {
        $periodos = collect();

        for ($i = 3; $i >= 0; $i--) {
            $inicio = $inicioSemana->copy()->subWeeks($i);
            $fin = $finSemana->copy()->subWeeks($i);
            $gasto = (float) Nomina::whereDate('fecha_inicio', $inicio->format('Y-m-d'))
                ->whereDate('fecha_fin', $fin->format('Y-m-d'))
                ->where('pagado', true)
                ->sum('pago_neto');

            $periodos->push([
                'label' => 'Sem. ' . $inicio->weekOfYear,
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
                'gasto' => round($gasto, 2),
            ]);
        }

        $actual = (float) ($periodos->last()['gasto'] ?? 0);
        $anterior = (float) ($periodos->slice(-2, 1)->first()['gasto'] ?? 0);

        return [
            'labels' => $periodos->pluck('label')->values(),
            'datos' => $periodos->pluck('gasto')->values(),
            'actual' => round($actual, 2),
            'anterior' => round($anterior, 2),
            'variacion' => round($actual - $anterior, 2),
        ];
    }

    private function mapaCalorAusentismo(Carbon $hoy): array
    {
        $dias = [
            Carbon::MONDAY => 'Lun',
            Carbon::TUESDAY => 'Mar',
            Carbon::WEDNESDAY => 'Mie',
            Carbon::THURSDAY => 'Jue',
            Carbon::FRIDAY => 'Vie',
            Carbon::SATURDAY => 'Sab',
        ];

        $faltas = array_fill_keys(array_values($dias), 0);
        $retardos = array_fill_keys(array_values($dias), 0);

        $asistencias = $this->asistenciasDashboard()
            ->with('empleado')
            ->whereBetween('fecha', [$hoy->copy()->startOfMonth()->format('Y-m-d'), $hoy->copy()->endOfMonth()->format('Y-m-d')])
            ->get();

        foreach ($asistencias as $asistencia) {
            $dia = Carbon::parse($asistencia->fecha)->dayOfWeek;
            if (!isset($dias[$dia])) {
                continue;
            }

            $label = $dias[$dia];

            if ($asistencia->tipo_asistencia === 'Falta') {
                $faltas[$label]++;
            }

            if ((int) ($asistencia->minutos_tarde ?? 0) > 0
                && $this->empleadoCuentaParaPuntualidad($asistencia->empleado)
                && $this->asistenciaTieneRetardoValido($asistencia)) {
                $retardos[$label]++;
            }
        }

        return [
            'labels' => array_values($dias),
            'series' => [
                [
                    'name' => 'Faltas',
                    'data' => collect($faltas)->map(fn ($valor, $dia) => ['x' => $dia, 'y' => $valor])->values(),
                ],
                [
                    'name' => 'Retardos',
                    'data' => collect($retardos)->map(fn ($valor, $dia) => ['x' => $dia, 'y' => $valor])->values(),
                ],
            ],
        ];
    }

    private function tasaPuntualidadGlobal(Carbon $inicioSemana, Carbon $finSemana): array
    {
        $empleados = Empleado::where('estatus', true)
            ->get()
            ->filter(fn ($empleado) => $this->empleadoCuentaParaPuntualidad($empleado))
            ->values();

        $total = $empleados->count();

        if ($total === 0) {
            return ['porcentaje' => 100, 'perfectos' => 0, 'evaluados' => 0];
        }

        $asistencias = Asistencia::with('empleado')
            ->whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')])
            ->get();

        $empleadosConFalta = $asistencias
            ->where('tipo_asistencia', 'Falta')
            ->pluck('empleado_id')
            ->unique();

        $empleadosConRetardoDescontable = $asistencias
            ->where('tipo_asistencia', 'Normal')
            ->filter(fn ($asistencia) => $this->asistenciaTieneRetardoValido($asistencia))
            ->groupBy('empleado_id')
            ->filter(fn ($registros) => (int) $registros->sum('minutos_tarde') >= self::UMBRAL_RETARDO_SEMANAL_MINUTOS)
            ->keys();

        $conIncidencia = $empleadosConFalta->merge($empleadosConRetardoDescontable)->unique()->count();
        $perfectos = max(0, $total - $conIncidencia);

        return [
            'porcentaje' => round(($perfectos / $total) * 100, 1),
            'perfectos' => $perfectos,
            'evaluados' => $total,
        ];
    }

    private function rotacionMes(Carbon $hoy): array
    {
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();
        $labels = [];
        $altas = [];
        $bajas = [];
        $inicio = $inicioMes->copy();
        $contador = 1;

        while ($inicio->lte($finMes)) {
            $fin = $inicio->copy()->addDays(6);
            if ($fin->gt($finMes)) {
                $fin = $finMes->copy();
            }
            $labels[] = 'S' . $contador;
            $altas[] = Empleado::whereBetween('fecha_ingreso', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])->count();
            $bajas[] = Empleado::whereBetween('fecha_baja', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])->count();
            $inicio = $fin->copy()->addDay();
            $contador++;
        }

        return [
            'labels' => $labels,
            'altas' => $altas,
            'bajas' => $bajas,
            'totalAltas' => array_sum($altas),
            'totalBajas' => array_sum($bajas),
        ];
    }

    private function pasivoVacacional(): array
    {
        $empleados = Empleado::where('estatus', true)->get();
        $dias = $empleados->sum(fn ($empleado) => max(0, (float) $empleado->dias_vacaciones_restantes));

        return [
            'diasPendientes' => round($dias, 2),
            'empleadosConSaldo' => $empleados->filter(fn ($empleado) => (float) $empleado->dias_vacaciones_restantes > 0)->count(),
        ];
    }

    private function distribucionAntiguedad(): array
    {
        $empleados = Empleado::where('estatus', true)->get();

        return [
            'labels' => ['Menos de 1 año', '1 a 3 años', 'Mas de 3 años'],
            'datos' => [
                $empleados->filter(fn ($empleado) => (float) $empleado->antiguedad_anios < 1)->count(),
                $empleados->filter(fn ($empleado) => (float) $empleado->antiguedad_anios >= 1 && (float) $empleado->antiguedad_anios <= 3)->count(),
                $empleados->filter(fn ($empleado) => (float) $empleado->antiguedad_anios > 3)->count(),
            ],
        ];
    }

    private function asistenciasDashboard()
    {
        return Asistencia::whereHas('empleado', function ($query) {
            $query->where(function ($empleado) {
                $empleado->where('es_estudiante', false)
                    ->orWhereNull('es_estudiante');
            });
        });
    }

    private function liderRetardos(Carbon $inicio, Carbon $fin, string $periodo): array
    {
        $asistencias = Asistencia::with('empleado')
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('tipo_asistencia', 'Normal')
            ->where('minutos_tarde', '>', 0)
            ->get()
            ->filter(function ($asistencia) {
                return $this->empleadoCuentaParaPuntualidad($asistencia->empleado)
                    && $this->asistenciaTieneRetardoValido($asistencia);
            });

        $retardosConUmbral = $asistencias
            ->groupBy(fn ($asistencia) => $asistencia->empleado_id . '|' . $this->claveSemanaNomina($asistencia->fecha))
            ->filter(fn ($registros) => (int) $registros->sum('minutos_tarde') >= self::UMBRAL_RETARDO_SEMANAL_MINUTOS)
            ->flatMap(fn ($registros) => $registros)
            ->values();

        $lider = $retardosConUmbral
            ->groupBy('empleado_id')
            ->map(function ($registros) {
                $empleado = $registros->first()->empleado;
                $peor = $registros->sortByDesc('minutos_tarde')->first();
                $minutos = (int) $registros->sum('minutos_tarde');

                return [
                    'empleado_id' => $empleado->id,
                    'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
                    'nombre_completo' => $empleado->nombre_completo,
                    'minutos' => $minutos,
                    'dias' => $registros->count(),
                    'peor_retardo' => (int) ($peor->minutos_tarde ?? 0),
                    'fecha_peor_retardo' => $peor ? Carbon::parse($peor->fecha)->format('Y-m-d') : null,
                ];
            })
            ->sortByDesc('minutos')
            ->values()
            ->first();

        return [
            'periodo' => $periodo,
            'inicio' => $inicio->format('Y-m-d'),
            'fin' => $fin->format('Y-m-d'),
            'lider' => $lider,
        ];
    }

    private function liderLlegadasTempranas(Carbon $inicio, Carbon $fin, string $periodo): array
    {
        $asistencias = Asistencia::with('empleado')
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('tipo_asistencia', 'Normal')
            ->whereNotNull('hora_entrada')
            ->get()
            ->filter(function ($asistencia) {
                return $this->empleadoCuentaParaPuntualidad($asistencia->empleado)
                    && !$this->esFinDeSemana($asistencia->fecha)
                    && $this->minutosAntesDeEntrada($asistencia->fecha, $asistencia->hora_entrada) > 0;
            })
            ->map(function ($asistencia) {
                $asistencia->minutos_temprano = $this->minutosAntesDeEntrada($asistencia->fecha, $asistencia->hora_entrada);

                return $asistencia;
            });

        $lider = $asistencias
            ->groupBy('empleado_id')
            ->map(function ($registros) {
                $empleado = $registros->first()->empleado;
                $mejor = $registros->sortByDesc('minutos_temprano')->first();

                return [
                    'empleado_id' => $empleado->id,
                    'numero_empleado' => $empleado->numero_empleado ?? $empleado->numero_empleado_baja,
                    'nombre_completo' => $empleado->nombre_completo,
                    'minutos' => (int) $registros->sum('minutos_temprano'),
                    'dias' => $registros->count(),
                    'mayor_anticipacion' => (int) ($mejor->minutos_temprano ?? 0),
                    'fecha_mayor_anticipacion' => $mejor ? Carbon::parse($mejor->fecha)->format('Y-m-d') : null,
                ];
            })
            ->sortByDesc('minutos')
            ->values()
            ->first();

        return [
            'periodo' => $periodo,
            'inicio' => $inicio->format('Y-m-d'),
            'fin' => $fin->format('Y-m-d'),
            'lider' => $lider,
        ];
    }

    private function empleadoCuentaParaPuntualidad(?Empleado $empleado): bool
    {
        return $empleado
            && (bool) ($empleado->estatus ?? false)
            && !(bool) ($empleado->es_estudiante ?? false)
            && !ReglasNominaEmpleado::sinRetardos($empleado);
    }

    private function minutosAntesDeEntrada($fecha, $horaEntrada): int
    {
        if (!$horaEntrada) {
            return 0;
        }

        $fechaBase = Carbon::parse($fecha)->format('Y-m-d');
        $entrada = Carbon::parse($fechaBase . ' ' . $horaEntrada);
        $horaOficial = Carbon::parse($fechaBase . ' 08:00:00');

        if ($entrada->greaterThanOrEqualTo($horaOficial)) {
            return 0;
        }

        return (int) $entrada->diffInMinutes($horaOficial);
    }

    private function asistenciaTieneRetardoValido(Asistencia $asistencia): bool
    {
        if ($this->esFinDeSemana($asistencia->fecha) || !$asistencia->hora_entrada || !$asistencia->hora_salida) {
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

    private function claveSemanaNomina($fecha): string
    {
        $dia = Carbon::parse($fecha)->startOfDay();
        $diasHastaMiercoles = (Carbon::WEDNESDAY - $dia->dayOfWeek + 7) % 7;

        return $dia->addDays($diasHastaMiercoles)->format('Y-m-d');
    }

    private function esFinDeSemana($fecha): bool
    {
        return Carbon::parse($fecha)->isWeekend();
    }
}
