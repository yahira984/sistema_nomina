<?php

namespace App\Jobs;

use App\Http\Controllers\AsistenciaController;
use App\Models\SystemOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrepareAttendanceImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(public string $operationId)
    {
        $this->onQueue('imports');
    }

    public function handle(AsistenciaController $controller): void
    {
        $operation = SystemOperation::findOrFail($this->operationId);

        if (in_array($operation->status, ['cancelled', 'consumed', 'dismissed'], true)) {
            return;
        }

        $payload = $operation->payload ?? [];
        $path = (string) ($payload['stored_path'] ?? '');

        $operation->markRunning('Leyendo marcajes del reloj...');

        try {
            $preview = $controller->prepararRevisionImportacion(
                Storage::disk('local')->path($path),
                $payload['fecha_inicio'] ?? null,
                $payload['fecha_fin'] ?? null,
                fn (int $progress, string $message) => $operation->reportProgress($progress, $message)
            );

            $operation->markCompleted(
                'Archivo listo para revisión.',
                ['preview' => $preview]
            );
            if ($path !== '') {
                Storage::disk('local')->delete($path);
            }
        } catch (Throwable $exception) {
            $operation->markFailed($exception, 'No se pudo analizar el archivo del reloj.');
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $operation = SystemOperation::find($this->operationId);
        $path = (string) data_get($operation?->payload, 'stored_path', '');

        if ($path !== '') {
            Storage::disk('local')->delete($path);
        }
    }
}
