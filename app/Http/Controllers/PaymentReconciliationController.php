<?php

namespace App\Http\Controllers;

use App\Jobs\ReconcilePaymentsJob;
use App\Services\SystemOperationService;
use App\Support\SemanaNomina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentReconciliationController extends Controller
{
    public function store(Request $request, SystemOperationService $operations)
    {
        $validated = $request->validate([
            'fecha_corte' => ['required', 'date'],
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);
        [$start, $end] = SemanaNomina::desdeCorte($validated['fecha_corte']);
        $file = $request->file('archivo');
        $path = $file->store('imports/reconciliation', 'local');
        $operation = $operations->create('payment_reconciliation', $request->user(), [
            'stored_path' => $path,
            'source_name' => $file->getClientOriginalName(),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
        ], 'reconciliation-' . hash_file('sha256', $file->getRealPath()) . '-' . $end->toDateString());

        if ($operation->wasRecentlyCreated) {
            ReconcilePaymentsJob::dispatch($operation->id)
                ->onConnection($operations->queueConnection('imports'));
        } else {
            Storage::disk('local')->delete($path);
        }

        return back()->with('success', 'La conciliación se está procesando en segundo plano.');
    }
}
