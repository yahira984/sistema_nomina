<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Empleado;
use App\Models\Asistencia;
use App\Models\Nomina;
use App\Support\ReglasNominaEmpleado;
use App\Support\SemanaNomina;
use Carbon\Carbon;

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

        // 2. INDICADORES RÁPIDOS
        $totalEmpleados = Empleado::where('estatus', true)->count();
        $gastoSemanal = Nomina::whereDate('fecha_inicio', $inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $finSemana->format('Y-m-d'))
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
        ]);
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
