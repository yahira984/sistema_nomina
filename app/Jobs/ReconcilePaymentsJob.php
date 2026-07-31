<?php

namespace App\Jobs;

use App\Models\SystemOperation;
use App\Services\PaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReconcilePaymentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;
    public bool $failOnTimeout = true;

    public function __construct(public string $operationId)
    {
        $this->onQueue('imports');
    }

    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(PaymentReconciliationService $service): void
    {
        $operation = SystemOperation::findOrFail($this->operationId);
        $payload = $operation->payload ?? [];
        $operation->markRunning('Leyendo movimientos bancarios...');

        try {
            $operation->reportProgress(35, 'Relacionando cuentas y empleados...');
            $reconciliation = $service->reconcile(
                Storage::disk('local')->path($payload['stored_path']),
                Carbon::parse($payload['period_start']),
                Carbon::parse($payload['period_end']),
                $payload['source_name'],
                $operation->user_id
            );
            $operation->markCompleted('Conciliación terminada.', [
                'reconciliation_id' => $reconciliation->id,
                'matched' => $reconciliation->matched_count,
                'differences' => $reconciliation->difference_count,
                'unmatched' => $reconciliation->unmatched_count,
                'results' => $reconciliation->results,
            ]);
        } catch (Throwable $exception) {
            $operation->markFailed($exception, 'No se pudo conciliar el archivo bancario.');
            throw $exception;
        } finally {
            Storage::disk('local')->delete((string) ($payload['stored_path'] ?? ''));
        }
    }
}
