<?php

namespace App\Http\Controllers;

use App\Services\PayrollPeriodService;
use App\Support\SemanaNomina;
use Illuminate\Http\Request;

class PayrollPeriodController extends Controller
{
    public function update(Request $request, PayrollPeriodService $periods)
    {
        $validated = $request->validate([
            'fecha_corte' => ['required', 'date'],
            'locked' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        [$start, $end] = SemanaNomina::desdeCorte($validated['fecha_corte']);
        $period = $periods->findOrCreate($start, $end);
        $periods->setLocked($period, (bool) $validated['locked'], $request->user(), $validated['notes'] ?? null);

        return back()->with(
            'success',
            $validated['locked'] ? 'Semana cerrada para cambios.' : 'Semana reabierta.'
        );
    }
}
