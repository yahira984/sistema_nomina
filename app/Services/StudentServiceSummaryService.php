<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentServiceSummaryService
{
    public function forEmployee(Empleado $empleado): array
    {
        $attendances = $this->attendanceQuery(collect([$empleado]))->get();

        return $this->build($empleado, $attendances);
    }

    public function forEmployees(Collection $employees): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        $grouped = $this->attendanceQuery($employees)->get()->groupBy('empleado_id');

        return $employees->mapWithKeys(fn (Empleado $employee) => [
            $employee->id => $this->build($employee, $grouped->get($employee->id, collect())),
        ]);
    }

    private function attendanceQuery(Collection $employees)
    {
        $earliestStart = $employees
            ->map(fn (Empleado $employee) => $this->startDate($employee))
            ->filter()
            ->min();

        return Asistencia::query()
            ->select(['id', 'empleado_id', 'fecha', 'hora_entrada', 'hora_salida', 'horas_trabajadas', 'horas_extra', 'tipo_asistencia'])
            ->whereIn('empleado_id', $employees->pluck('id'))
            ->where('tipo_asistencia', 'Normal')
            ->when($earliestStart, fn ($query) => $query->whereDate('fecha', '>=', $earliestStart));
    }

    private function build(Empleado $employee, Collection $attendances): array
    {
        $start = $this->startDate($employee);
        $end = $employee->fecha_baja ? Carbon::parse($employee->fecha_baja)->startOfDay() : null;
        $valid = $attendances
            ->filter(function ($attendance) use ($start, $end) {
                $date = Carbon::parse($attendance->fecha)->startOfDay();
                return (!$start || $date->gte($start)) && (!$end || $date->lte($end));
            })
            ->sortBy('fecha')
            ->values();

        $completed = round($valid->sum(fn ($attendance) => $this->hours($attendance)), 2);
        $required = max(0, (float) ($employee->horas_servicio_requeridas ?? 0));
        $remaining = max(0, round($required - $completed, 2));
        $days = $valid->pluck('fecha')->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))->unique()->count();
        $average = $days > 0 ? round($completed / $days, 2) : 0;
        $estimated = $remaining > 0 && $average > 0
            ? Carbon::today()->addDays((int) ceil($remaining / $average))->format('Y-m-d')
            : null;
        $deadline = $employee->fecha_limite_servicio
            ? Carbon::parse($employee->fecha_limite_servicio)->format('Y-m-d')
            : null;
        $atRisk = $remaining > 0 && $deadline && (!$estimated || Carbon::parse($estimated)->gt(Carbon::parse($deadline)));

        return [
            'horas_cumplidas' => $completed,
            'horas_requeridas' => $required,
            'horas_restantes' => $remaining,
            'porcentaje' => $required > 0 ? round(min(100, ($completed / $required) * 100), 1) : 0,
            'dias_con_registro' => $days,
            'promedio_horas_dia' => $average,
            'primera_asistencia' => $valid->first()?->fecha ? Carbon::parse($valid->first()->fecha)->format('Y-m-d') : null,
            'ultima_asistencia' => $valid->last()?->fecha ? Carbon::parse($valid->last()->fecha)->format('Y-m-d') : null,
            'fecha_estimada_termino' => $estimated,
            'fecha_limite' => $deadline,
            'en_riesgo' => (bool) $atRisk,
            'mensaje_alerta' => $atRisk
                ? ($estimated
                    ? "Al ritmo actual terminaría el {$estimated}, después de la fecha límite {$deadline}."
                    : "Aún no hay suficientes asistencias para estimar el término antes de la fecha límite {$deadline}.")
                : null,
            'fecha_inicio' => $start?->format('Y-m-d'),
            'fecha_fin' => $end?->format('Y-m-d'),
            'estado' => $this->status($employee, $required, $completed),
        ];
    }

    private function startDate(Empleado $employee): ?Carbon
    {
        $date = $employee->fecha_inicio_servicio ?: $employee->inicioPeriodoActual();
        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    private function hours($attendance): float
    {
        $stored = (float) $attendance->horas_trabajadas + (float) $attendance->horas_extra;
        if ($stored > 0) {
            return round($stored, 2);
        }

        if (!$attendance->hora_entrada || !$attendance->hora_salida) {
            return 0;
        }

        $date = Carbon::parse($attendance->fecha)->format('Y-m-d');
        $start = Carbon::parse("{$date} {$attendance->hora_entrada}");
        $end = Carbon::parse("{$date} {$attendance->hora_salida}");

        return $end->gt($start) ? round($start->diffInMinutes($end) / 60, 2) : 0;
    }

    private function status(Empleado $employee, float $required, float $completed): string
    {
        if ((bool) $employee->servicio_pausado) return 'Pausado';
        if ($required > 0 && $completed >= $required) return 'Completado';
        if (!(bool) $employee->estatus) return 'Baja con horas pendientes';
        return 'En curso';
    }
}
