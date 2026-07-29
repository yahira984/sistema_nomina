<?php

namespace App\Jobs;

use App\Http\Controllers\NominaController;
use App\Models\SystemOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateMassPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public bool $failOnTimeout = true;

    public function __construct(public string $operationId)
    {
        $this->onQueue('exports');
    }

    public function handle(NominaController $controller): void
    {
        $operation = SystemOperation::findOrFail($this->operationId);
        $payload = $operation->payload ?? [];
        $type = (string) ($payload['export_type'] ?? '');
        $request = Request::create('/', 'GET', [
            'fecha_corte' => $payload['fecha_corte'] ?? null,
            'empleado_ids' => $payload['empleado_ids'] ?? [],
        ]);

        $operation->markRunning('Preparando datos del periodo...');

        try {
            $operation->reportProgress(35, 'Calculando y armando recibos...');

            $response = match ($type) {
                'payroll_pdf' => $controller->generarRecibosMasivos($request),
                'imss_pdf' => $controller->recibosDiferenciaImss($request, $payload['week_number'] ?? 0),
                default => throw new \RuntimeException("Tipo de PDF no soportado: {$type}"),
            };

            $operation->reportProgress(80, 'Guardando el PDF...');
            $content = $response->getContent();
            $fileName = ($type === 'imss_pdf' ? 'Diferencias_IMSS' : 'Recibos_Nomina')
                . '_' . now()->format('Ymd_His') . '.pdf';
            $path = 'exports/' . $operation->id . '/' . $fileName;

            Storage::disk('local')->put($path, $content);

            $operation->markCompleted(
                'PDF generado y listo para descargar.',
                ['export_type' => $type],
                $path,
                $fileName
            );
        } catch (Throwable $exception) {
            $operation->markFailed($exception, 'No se pudo generar el PDF.');
            throw $exception;
        }
    }
}
