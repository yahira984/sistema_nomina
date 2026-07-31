<?php

namespace App\Jobs;

use App\Models\SystemOperation;
use App\Services\DatabaseBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CreateVerifiedBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 1200;
    public bool $failOnTimeout = true;

    public function __construct(public string $operationId, public bool $testRestore = false)
    {
        $this->onQueue('exports');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(DatabaseBackupService $service): void
    {
        $operation = SystemOperation::findOrFail($this->operationId);
        $operation->markRunning('Generando respaldo por bloques...');

        try {
            $backup = $service->createAutomatic();
            $operation->reportProgress(70, 'Verificando integridad del respaldo...');
            $backup = $service->verify($backup);

            if ($this->testRestore) {
                $operation->reportProgress(85, 'Probando restauración aislada...');
                $backup = $service->testRestore($backup);
            }

            if (!in_array($backup->status, ['verified'], true)) {
                throw new \RuntimeException($backup->verification_message ?: 'El respaldo no superó la verificación.');
            }

            $operation->markCompleted('Respaldo creado y verificado correctamente.', [
                'backup_id' => $backup->id,
                'size_bytes' => $backup->size_bytes,
                'verification' => $backup->verification_message,
            ]);
        } catch (Throwable $exception) {
            $operation->markFailed($exception, 'No se pudo crear o verificar el respaldo.');
            throw $exception;
        }
    }
}
