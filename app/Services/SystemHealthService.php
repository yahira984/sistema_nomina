<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\IntegrationFailure;
use App\Models\Nomina;
use App\Models\SystemBackup;
use App\Models\SystemOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemHealthService
{
    public function snapshot(): array
    {
        return [
            'services' => [
                $this->databaseStatus(),
                $this->firebaseStatus(),
                $this->storageStatus(),
                $this->queueStatus(),
                $this->backupStatus(),
            ],
            'inconsistencies' => $this->inconsistencies(),
            'generated_at' => now()->toISOString(),
        ];
    }

    public function inconsistencies(): array
    {
        $duplicateEmployees = Empleado::query()
            ->whereNotNull('numero_empleado')
            ->where('numero_empleado', '!=', '')
            ->select('numero_empleado', DB::raw('COUNT(*) as total'))
            ->groupBy('numero_empleado')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(fn ($row) => [
                'key' => $row->numero_empleado,
                'count' => (int) $row->total,
            ])
            ->values();

        $missingPhotos = Empleado::query()
            ->where('estatus', true)
            ->get(['id', 'numero_empleado', 'nombre_completo'])
            ->filter(fn (Empleado $employee) => !$this->employeeHasPhoto($employee))
            ->map(fn (Empleado $employee) => [
                'id' => $employee->id,
                'numero_empleado' => $employee->numero_empleado,
                'nombre_completo' => $employee->nombre_completo,
            ])
            ->values();

        $orphanAttendance = Schema::hasTable('asistencias')
            ? Asistencia::query()->whereDoesntHave('empleado')->count()
            : 0;
        $orphanPayroll = Schema::hasTable('nominas')
            ? Nomina::query()->whereDoesntHave('empleado')->count()
            : 0;
        $openFailures = Schema::hasTable('integration_failures')
            ? IntegrationFailure::where('status', '!=', 'resolved')->count()
            : 0;

        return [
            'duplicate_employees' => $duplicateEmployees,
            'missing_photos' => $missingPhotos,
            'orphan_attendance' => $orphanAttendance,
            'orphan_payroll' => $orphanPayroll,
            'open_integration_failures' => $openFailures,
            'total' => $duplicateEmployees->sum('count')
                + $missingPhotos->count()
                + $orphanAttendance
                + $orphanPayroll
                + $openFailures,
        ];
    }

    private function databaseStatus(): array
    {
        try {
            DB::select('SELECT 1');

            return $this->service('database', 'Base de datos', 'healthy', 'Conexión disponible');
        } catch (Throwable $exception) {
            return $this->service('database', 'Base de datos', 'critical', $exception->getMessage());
        }
    }

    private function firebaseStatus(): array
    {
        $configured = (bool) config('services.firebase.database_url')
            && (bool) config('services.firebase.credentials');
        $failures = Schema::hasTable('integration_failures')
            ? IntegrationFailure::where('integration', 'firebase')->where('status', '!=', 'resolved')->count()
            : 0;

        if (!$configured) {
            return $this->service('firebase', 'Firebase', 'warning', 'Configuración incompleta');
        }

        return $this->service(
            'firebase',
            'Firebase',
            $failures > 0 ? 'warning' : 'healthy',
            $failures > 0 ? "{$failures} sincronización(es) pendientes" : 'Configurado y sin errores pendientes'
        );
    }

    private function storageStatus(): array
    {
        try {
            $free = disk_free_space(storage_path());
            $total = disk_total_space(storage_path());
            $percent = $total > 0 ? round(($free / $total) * 100, 1) : 0;

            return $this->service(
                'storage',
                'Almacenamiento',
                $percent < 10 ? 'critical' : ($percent < 20 ? 'warning' : 'healthy'),
                "{$percent}% libre"
            );
        } catch (Throwable $exception) {
            return $this->service('storage', 'Almacenamiento', 'warning', 'No se pudo medir el espacio');
        }
    }

    private function queueStatus(): array
    {
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $heartbeat = Cache::get('system:queue-heartbeat');
        $heartbeatAge = $heartbeat
            ? (int) floor(abs(now()->diffInMinutes($heartbeat)))
            : null;
        $workerAvailable = $heartbeatAge !== null && $heartbeatAge <= 3;
        $status = match (true) {
            $failed > 0 => 'critical',
            !$workerAvailable => 'warning',
            $pending > 20 => 'warning',
            default => 'healthy',
        };
        $heartbeatMessage = $workerAvailable
            ? "worker activo hace {$heartbeatAge} min"
            : 'sin latido reciente del worker';

        return $this->service(
            'queue',
            'Cola de trabajos',
            $status,
            "{$pending} pendiente(s), {$failed} fallido(s); {$heartbeatMessage}"
        );
    }

    private function backupStatus(): array
    {
        $backup = Schema::hasTable('system_backups') ? SystemBackup::latest()->first() : null;

        if (!$backup) {
            return $this->service('backup', 'Respaldos', 'warning', 'Todavía no hay respaldo automático');
        }

        $age = $backup->created_at
            ? (int) floor(abs($backup->created_at->diffInHours(now())))
            : 999;
        $status = $backup->status === 'verified' && $age <= 48 ? 'healthy' : 'warning';

        return $this->service(
            'backup',
            'Respaldos',
            $status,
            "Último respaldo hace {$age} h; estado {$backup->status}"
        );
    }

    private function employeeHasPhoto(Empleado $employee): bool
    {
        $base = public_path('img/empleados');
        $keys = array_filter([
            preg_replace('/[^A-Za-z0-9_-]/', '', (string) $employee->numero_empleado),
            "id-{$employee->id}",
            "empleado-{$employee->id}",
        ]);

        foreach ($keys as $key) {
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
                if (is_file($base . DIRECTORY_SEPARATOR . "{$key}.{$extension}")) {
                    return true;
                }
            }
        }

        return false;
    }

    private function service(string $key, string $label, string $status, string $message): array
    {
        return compact('key', 'label', 'status', 'message');
    }
}
