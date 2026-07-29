<?php

namespace App\Services;

use App\Jobs\SyncFirebaseJob;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;

class FirebaseJobDispatcher
{
    public static function employee(Empleado $employee, bool $full = false): void
    {
        self::dispatch($full ? 'employee_full' : 'employee', [
            'empleado_id' => $employee->id,
            'reference_type' => Empleado::class,
            'reference_id' => $employee->id,
        ]);
    }

    public static function attendance(Asistencia $attendance): void
    {
        self::dispatch('attendance', [
            'asistencia_id' => $attendance->id,
            'reference_type' => Asistencia::class,
            'reference_id' => $attendance->id,
        ]);
    }

    public static function attendances(iterable $attendances): void
    {
        $ids = collect($attendances)
            ->map(fn ($attendance) => $attendance instanceof Asistencia ? $attendance->id : (int) $attendance)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach (array_chunk($ids, 250) as $chunk) {
            self::dispatch('attendance_batch', [
                'asistencia_ids' => $chunk,
                'reference_type' => Asistencia::class,
                'reference_id' => $chunk[0] ?? null,
            ]);
        }
    }

    public static function deleteAttendance(Asistencia $attendance): void
    {
        self::dispatch('attendance_delete', [
            'attendance' => [
                'id' => $attendance->id,
                'empleado_id' => $attendance->empleado_id,
                'fecha' => $attendance->fecha,
            ],
            'reference_type' => Asistencia::class,
            'reference_id' => $attendance->id,
        ]);
    }

    public static function paidPayroll(Nomina $payroll, array $breakdown = []): void
    {
        self::dispatch('payroll_paid', [
            'nomina_id' => $payroll->id,
            'breakdown' => $breakdown,
            'reference_type' => Nomina::class,
            'reference_id' => $payroll->id,
        ]);
    }

    public static function deletePayroll(Nomina $payroll): void
    {
        self::dispatch('payroll_delete', [
            'nomina_id' => $payroll->id,
            'reference_type' => Nomina::class,
            'reference_id' => $payroll->id,
        ]);
    }

    private static function dispatch(string $operation, array $payload): void
    {
        SyncFirebaseJob::dispatch($operation, $payload)
            ->onConnection(config('services.firebase.queue_connection', 'deferred'))
            ->afterCommit();
    }
}
