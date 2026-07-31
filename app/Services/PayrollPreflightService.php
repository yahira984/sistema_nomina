<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Support\HorarioLaboralEmpleado;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayrollPreflightService
{
    public function inspect(Carbon $start, Carbon $end, array $employeeIds = []): array
    {
        $employees = Empleado::query()
            ->where('estatus', true)
            ->when($employeeIds !== [], fn ($query) => $query->whereIn('id', $employeeIds))
            ->where(fn ($query) => $query->whereNull('fecha_ingreso')->orWhereDate('fecha_ingreso', '<=', $end))
            ->get([
                'id', 'numero_empleado', 'numero_empleado_baja', 'nombre_completo', 'puesto',
                'sueldo_por_hora', 'sueldo_semanal', 'fecha_ingreso', 'estatus',
            ]);
        $inspectedEmployeeIds = $employees->pluck('id');
        $attendance = Asistencia::query()
            ->whereIn('empleado_id', $inspectedEmployeeIds)
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'empleado_id', 'fecha', 'tipo_asistencia', 'hora_entrada', 'hora_salida'])
            ->groupBy('empleado_id');

        $issues = collect();
        $this->inspectDuplicateNumbers($issues, $employees);

        foreach ($employees as $employee) {
            $number = $employee->numero_empleado ?: $employee->numero_empleado_baja ?: "ID {$employee->id}";
            $label = "#{$number} - {$employee->nombre_completo}";

            if ((float) $employee->sueldo_por_hora <= 0 && (float) $employee->sueldo_semanal <= 0) {
                $issues->push($this->issue('missing_salary', 'critical', $label, 'No tiene sueldo por hora ni sueldo semanal configurado.'));
            }

            if ($this->requiresSpecialRule($employee) && empty(WorkRuleResolver::for($employee)['rule_ids'])) {
                $issues->push($this->issue('missing_rule', 'critical', $label, 'El puesto parece requerir un turno especial, pero no tiene una regla laboral asignada.'));
            }

            $employeeAttendance = $attendance->get($employee->id, collect())->keyBy(fn ($row) => Carbon::parse($row->fecha)->toDateString());
            foreach (CarbonPeriod::create($start, $end) as $date) {
                if (!HorarioLaboralEmpleado::esDiaLaboral($employee, $date)) {
                    continue;
                }

                $record = $employeeAttendance->get($date->toDateString());
                if (!$record) {
                    $issues->push($this->issue('missing_attendance', 'critical', $label, "Falta capturar {$date->format('d/m/Y')}."));
                    continue;
                }

                if ($record->tipo_asistencia === 'Normal' && (!$record->hora_entrada || !$record->hora_salida)) {
                    $issues->push($this->issue('incomplete_mark', 'critical', $label, "Entrada o salida incompleta el {$date->format('d/m/Y')}."));
                }
            }
        }

        $anomalies = $this->payrollAnomalies($start, $end, $employeeIds);
        $issues = $issues->concat($anomalies)->values();
        $critical = $issues->where('severity', 'critical')->count();
        $warnings = $issues->where('severity', 'warning')->count();

        return [
            'ready' => $critical === 0,
            'critical_count' => $critical,
            'warning_count' => $warnings,
            'total_count' => $issues->count(),
            'checked_employees' => $employees->count(),
            'issues' => $issues->take(150)->values()->all(),
            'truncated' => $issues->count() > 150,
            'generated_at' => now()->toISOString(),
        ];
    }

    private function inspectDuplicateNumbers(Collection $issues, Collection $employees): void
    {
        $numbers = $employees->pluck('numero_empleado')->filter(fn ($number) => filled($number))->unique()->values();

        if ($numbers->isEmpty()) {
            return;
        }

        Empleado::query()
            ->whereNotNull('numero_empleado')
            ->where('numero_empleado', '!=', '')
            ->whereIn('numero_empleado', $numbers)
            ->select('numero_empleado', DB::raw('COUNT(*) as total'))
            ->groupBy('numero_empleado')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(fn ($row) => $issues->push($this->issue(
                'duplicate_employee_number',
                'critical',
                "Numero #{$row->numero_empleado}",
                "Está asignado a {$row->total} empleados."
            )));
    }

    private function payrollAnomalies(Carbon $start, Carbon $end, array $employeeIds = []): Collection
    {
        $current = Nomina::query()
            ->whereDate('fecha_inicio', $start)
            ->whereDate('fecha_fin', $end)
            ->when($employeeIds !== [], fn ($query) => $query->whereIn('empleado_id', $employeeIds))
            ->with('empleado:id,numero_empleado,nombre_completo')
            ->get(['id', 'empleado_id', 'pago_neto']);
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays(6);
        $previous = Nomina::query()
            ->whereDate('fecha_inicio', $previousStart)
            ->whereDate('fecha_fin', $previousEnd)
            ->when($employeeIds !== [], fn ($query) => $query->whereIn('empleado_id', $employeeIds))
            ->pluck('pago_neto', 'empleado_id');

        return $current->map(function (Nomina $payroll) use ($previous) {
            $before = (float) ($previous[$payroll->empleado_id] ?? 0);
            $current = (float) $payroll->pago_neto;
            if ($before <= 0 || abs($current - $before) / $before < 0.30) {
                return null;
            }

            $employee = $payroll->empleado;
            return $this->issue(
                'unusual_variation',
                'warning',
                '#' . ($employee?->numero_empleado ?: $payroll->empleado_id) . ' - ' . ($employee?->nombre_completo ?: 'Empleado'),
                sprintf('El pago neto cambió %.1f%% respecto a la semana anterior.', abs($current - $before) / $before * 100)
            );
        })->filter()->values();
    }

    private function requiresSpecialRule(Empleado $employee): bool
    {
        $position = mb_strtoupper((string) $employee->puesto);

        return str_contains($position, 'VIGILAN') || str_contains($position, '24X24') || str_contains($position, '24 X 24');
    }

    private function issue(string $code, string $severity, string $employee, string $message): array
    {
        return compact('code', 'severity', 'employee', 'message');
    }
}
