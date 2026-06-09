<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Empleado;
use App\Models\Asistencia;
use App\Models\Nomina;
use App\Support\SemanaNomina;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
        $faltasMes = Asistencia::where('tipo_asistencia', 'Falta')
            ->whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
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
            Asistencia::whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Normal')->count(),
            $faltasMes,
            Asistencia::whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Vacaciones')->count(),
            Asistencia::whereMonth('fecha', $mesActual)->whereYear('fecha', $anioActual)->where('tipo_asistencia', 'Incapacidad')->count(),
        ];

        // 5. DATOS GRÁFICA DE BARRAS (Horas Extra últimos 7 días)
        $ultimos7Dias = collect();
        for ($i = 6; $i >= 0; $i--) {
            $fecha = $hoy->copy()->subDays($i)->format('Y-m-d');
            $horas = Asistencia::where('fecha', $fecha)->sum('horas_extra');
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
            ]
        ]);
    }
}
