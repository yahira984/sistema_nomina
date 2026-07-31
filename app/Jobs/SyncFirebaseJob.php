<?php

namespace App\Jobs;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\IntegrationFailure;
use App\Models\Nomina;
use App\Models\SystemOperation;
use App\Services\FirebaseSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SyncFirebaseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $operation,
        public array $payload,
        public ?string $systemOperationId = null
    ) {
        $this->onQueue('integrations');
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        $systemOperation = $this->systemOperationId ? SystemOperation::find($this->systemOperationId) : null;
        $systemOperation?->markRunning('Sincronizando con Firebase...');

        $ok = match ($this->operation) {
            'employee' => $this->syncEmployee(false),
            'employee_full' => $this->syncEmployee(true),
            'attendance' => $this->syncAttendance(),
            'attendance_batch' => $this->syncAttendanceBatch(),
            'attendance_delete' => $this->deleteAttendance(),
            'payroll_paid' => $this->syncPayroll(),
            'payroll_delete' => $this->deletePayroll(),
            default => throw new RuntimeException("Operación Firebase desconocida: {$this->operation}"),
        };

        if (!$ok) {
            throw new RuntimeException('Firebase no confirmó la sincronización.');
        }

        IntegrationFailure::query()
            ->where('integration', 'firebase')
            ->where('operation', $this->operation)
            ->where('reference_type', $this->payload['reference_type'] ?? null)
            ->where('reference_id', $this->payload['reference_id'] ?? null)
            ->where('status', 'pending')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'last_attempt_at' => now(),
            ]);

        $systemOperation?->markCompleted('Firebase actualizado correctamente.', [
            'firebase_operation' => $this->operation,
            'reference_id' => $this->payload['reference_id'] ?? null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->systemOperationId) {
            SystemOperation::find($this->systemOperationId)?->markFailed(
                $exception ?: 'Firebase no respondió después de varios intentos.',
                'No fue posible sincronizar con Firebase.'
            );
        }

        IntegrationFailure::updateOrCreate(
            [
                'integration' => 'firebase',
                'operation' => $this->operation,
                'reference_type' => $this->payload['reference_type'] ?? null,
                'reference_id' => $this->payload['reference_id'] ?? null,
                'status' => 'pending',
            ],
            [
                'attempts' => $this->attempts(),
                'payload' => $this->payload,
                'error' => $exception?->getMessage() ?: 'Error desconocido',
                'last_attempt_at' => now(),
            ]
        );
    }

    private function syncEmployee(bool $full): bool
    {
        $employee = Empleado::find($this->payload['empleado_id'] ?? 0);

        if (!$employee) {
            return true;
        }

        return $full
            ? FirebaseSyncService::sincronizarEmpleadoCompleto($employee)
            : FirebaseSyncService::sincronizarEmpleado($employee);
    }

    private function syncAttendance(): bool
    {
        $attendance = Asistencia::with('empleado')->find($this->payload['asistencia_id'] ?? 0);

        return !$attendance || FirebaseSyncService::sincronizarAsistencia($attendance);
    }

    private function syncAttendanceBatch(): bool
    {
        $ids = collect($this->payload['asistencia_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique();

        if ($ids->isEmpty()) {
            return true;
        }

        return FirebaseSyncService::sincronizarAsistencias(
            Asistencia::with('empleado')->whereIn('id', $ids)->get()
        );
    }

    private function deleteAttendance(): bool
    {
        $attendance = new Asistencia($this->payload['attendance'] ?? []);
        $attendance->id = $this->payload['attendance']['id'] ?? null;
        $employee = Empleado::find($this->payload['attendance']['empleado_id'] ?? 0);

        if (!$employee) {
            return true;
        }

        $attendance->setRelation('empleado', $employee);

        return FirebaseSyncService::eliminarAsistencia($attendance);
    }

    private function syncPayroll(): bool
    {
        $payroll = Nomina::find($this->payload['nomina_id'] ?? 0);
        $employee = $payroll ? Empleado::find($payroll->empleado_id) : null;

        return !$payroll || !$employee || FirebaseSyncService::sincronizarNominaPagada(
            $employee,
            $payroll,
            $this->payload['breakdown'] ?? []
        );
    }

    private function deletePayroll(): bool
    {
        $payroll = Nomina::find($this->payload['nomina_id'] ?? 0);
        $employee = $payroll ? Empleado::find($payroll->empleado_id) : null;

        return !$payroll || !$employee || FirebaseSyncService::eliminarNominaPagada($employee, $payroll);
    }
}
