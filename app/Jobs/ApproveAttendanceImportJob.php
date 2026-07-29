<?php

namespace App\Jobs;

use App\Http\Controllers\AsistenciaController;
use App\Models\SystemOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ApproveAttendanceImportJob implements ShouldQueue
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

        $rows = $operation->payload['filas'] ?? [];

        $operation->markRunning('Guardando asistencias aprobadas...');

        try {
            $result = $controller->procesarFilasImportacion(
                $rows,
                fn (int $progress, string $message) => $operation->reportProgress($progress, $message)
            );

            $operation->markCompleted(
                "Importación terminada: {$result['guardadas']} guardada(s), {$result['omitidas']} omitida(s).",
                $result
            );
        } catch (Throwable $exception) {
            $operation->markFailed($exception, 'No se pudo aprobar la importación.');
            throw $exception;
        }
    }
}
