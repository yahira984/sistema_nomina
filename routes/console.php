<?php

use App\Models\Empleado;
use App\Models\Nomina;
use App\Services\FirebaseSyncService;
use App\Services\DatabaseBackupService;
use App\Services\SystemHealthService;
use App\Jobs\QueueHeartbeatJob;
use App\Jobs\CreateVerifiedBackupJob;
use App\Models\SystemOperation;
use App\Services\AnnualArchiveService;
use App\Services\SystemStorageCleanupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('firebase:sync-paid-nominas {--empleado_id=} {--nomina_id=}', function () {
    $query = Nomina::with('empleado')->where('pagado', true);

    if ($this->option('empleado_id')) {
        $query->where('empleado_id', $this->option('empleado_id'));
    }

    if ($this->option('nomina_id')) {
        $query->whereKey($this->option('nomina_id'));
    }

    $total = 0;
    $query->orderBy('id')->chunkById(50, function ($nominas) use (&$total) {
        foreach ($nominas as $nomina) {
            if (!$nomina->empleado) {
                $this->warn("Nomina {$nomina->id} sin empleado, se omitio.");
                continue;
            }

            FirebaseSyncService::sincronizarNominaPagada($nomina->empleado, $nomina, [
                'total_percepciones' => $nomina->total_percepciones,
                'total_deducciones' => $nomina->total_deducciones,
                'pago_neto' => $nomina->pago_neto,
            ]);

            $total++;
        }
    });

    $this->info("Nominas pagadas enviadas a Firebase: {$total}");
})->purpose('Sincroniza con Firebase las nominas que ya estan marcadas como pagadas.');

Artisan::command('firebase:sync-mobile {--empleado_id=} {--incluir-bajas}', function () {
    $query = Empleado::query()
        ->when(!$this->option('incluir-bajas'), fn ($query) => $query->where('estatus', true))
        ->when($this->option('empleado_id'), fn ($query) => $query->whereKey($this->option('empleado_id')))
        ->orderBy('id');

    $total = 0;

    $query->chunkById(25, function ($empleados) use (&$total) {
        foreach ($empleados as $empleado) {
            FirebaseSyncService::sincronizarEmpleadoCompleto($empleado);
            $total++;
            $this->line("Sincronizado empleado {$empleado->id} - {$empleado->nombre_completo}");
        }
    });

    $this->info("Empleados preparados para app movil: {$total}");
})->purpose('Sincroniza perfil, resumen, asistencias y nominas pagadas para la app movil.');

Artisan::command('firebase:link-user {uid} {empleado}', function (string $uid, string $empleado) {
    $empleadoModel = Empleado::where('numero_empleado', $empleado)
        ->orWhere('numero_empleado_baja', $empleado)
        ->first();

    if (!$empleadoModel && is_numeric($empleado)) {
        $empleadoModel = Empleado::find($empleado);
    }

    if (!$empleadoModel) {
        $this->error("No encontre empleado con numero o ID: {$empleado}");
        return 1;
    }

    FirebaseSyncService::vincularUsuarioMobile($uid, $empleadoModel);

    $this->info("Usuario Firebase {$uid} vinculado con empleado {$empleadoModel->id} - {$empleadoModel->nombre_completo}");
})->purpose('Vincula un UID de Firebase Auth con un empleado para que la app solo lea su informacion.');

Artisan::command('firebase:deploy-rules {--force}', function () {
    if (!$this->option('force')) {
        $this->error('Usa --force para confirmar la actualización remota de reglas.');
        return 1;
    }

    $path = base_path('firebase/database.rules.json');

    if (!is_file($path)) {
        $this->error("No existe el archivo de reglas: {$path}");
        return 1;
    }

    $rules = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    FirebaseSyncService::actualizarReglas($rules);
    $this->info('Reglas seguras de Firebase actualizadas correctamente.');
})->purpose('Publica las reglas versionadas de Realtime Database.');

Artisan::command('system:backup', function (DatabaseBackupService $backups) {
    $backup = $backups->createAutomatic();
    $this->info("Respaldo creado: {$backup->path}");
})->purpose('Genera un respaldo SQL automático con checksum.');

Artisan::command('system:backup-queue {--restore-test}', function () {
    $operation = SystemOperation::firstOrCreate(
        ['type' => 'verified_backup', 'idempotency_key' => 'scheduled-backup-' . now()->format('Y-m-d')],
        ['status' => 'queued', 'progress' => 0, 'message' => 'Respaldo programado en espera.']
    );
    if ($operation->wasRecentlyCreated) {
        CreateVerifiedBackupJob::dispatch($operation->id, (bool) $this->option('restore-test'));
    }
    $this->info("Respaldo en cola: {$operation->id}");
})->purpose('Programa un respaldo verificado sin bloquear el servidor web.');

Artisan::command('system:archive-year {year?}', function (AnnualArchiveService $archives) {
    $year = (int) ($this->argument('year') ?: now()->subYear()->year);
    $archive = $archives->create($year);
    $this->info("Archivo anual {$archive->year} verificado: {$archive->checksum}");
})->purpose('Genera un resumen anual verificable sin eliminar el historial detallado.');

Artisan::command('system:backup-verify', function (DatabaseBackupService $backups) {
    $backup = $backups->verifyLatest();

    if (!$backup) {
        $this->warn('No hay respaldos para verificar.');
        return 1;
    }

    $this->line($backup->verification_message);

    return $backup->status === 'verified' ? 0 : 1;
})->purpose('Verifica integridad y estructura crítica del último respaldo.');

Artisan::command('system:backup-restore-test', function (DatabaseBackupService $backups) {
    $backup = $backups->testLatestRestore();

    if (!$backup) {
        $this->warn('No hay respaldos para probar.');
        return 1;
    }

    $this->line($backup->verification_message);

    return $backup->status === 'verified' ? 0 : 1;
})->purpose('Restaura el último respaldo en una base aislada y elimina la base temporal.');

Artisan::command('system:health', function (SystemHealthService $health) {
    $snapshot = $health->snapshot();

    foreach ($snapshot['services'] as $service) {
        $this->line(strtoupper($service['status']) . " | {$service['label']}: {$service['message']}");
    }

    $this->line("Inconsistencias detectadas: {$snapshot['inconsistencies']['total']}");
})->purpose('Muestra el estado de base de datos, colas, Firebase, almacenamiento y respaldos.');

Artisan::command('system:cleanup', function (DatabaseBackupService $backups, SystemStorageCleanupService $storage) {
    $this->info('Respaldos eliminados: ' . $backups->cleanup(30));
    $this->info('Exportaciones eliminadas: ' . $storage->cleanupExports(14));
})->purpose('Elimina respaldos antiguos y exportaciones temporales vencidas.');

Schedule::command('system:backup-queue')->dailyAt('23:30')->withoutOverlapping();
Schedule::command('system:backup-verify')->weeklyOn(1, '02:00')->withoutOverlapping();
Schedule::command('system:backup-restore-test')->monthlyOn(1, '02:30')->withoutOverlapping();
Schedule::job(new QueueHeartbeatJob())
    ->name('system-queue-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=336')->dailyAt('03:00');
Schedule::command('model:prune')->dailyAt('03:30');
Schedule::command('system:cleanup')->dailyAt('03:45')->withoutOverlapping();
Schedule::command('system:archive-year')->yearlyOn(1, 2, '04:00')->withoutOverlapping();
