<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMassPdfJob;
use App\Services\SystemOperationService;
use App\Support\SemanaNomina;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AsyncExportController extends Controller
{
    public function store(Request $request, SystemOperationService $operations)
    {
        $validated = $request->validate([
            'export_type' => ['required', 'in:payroll_pdf,imss_pdf'],
            'fecha_corte' => ['required', 'date'],
            'empleado_ids' => ['nullable', 'array'],
            'empleado_ids.*' => ['integer', 'exists:empleados,id'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        [, , $week] = SemanaNomina::desdeCorte($validated['fecha_corte']);
        $operation = $operations->create(
            'mass_export',
            $request->user(),
            [
                'export_type' => $validated['export_type'],
                'fecha_corte' => $validated['fecha_corte'],
                'empleado_ids' => $validated['empleado_ids'] ?? [],
                'week_number' => $week,
            ],
            $validated['idempotency_key'] ?? (string) Str::uuid()
        );

        if ($operation->wasRecentlyCreated) {
            GenerateMassPdfJob::dispatch($operation->id);
        }

        return back()
            ->with('success', 'La exportación se está preparando en segundo plano.')
            ->with('operation_id', $operation->id);
    }
}
