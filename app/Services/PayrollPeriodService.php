<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PayrollPeriodService
{
    public function findOrCreate(Carbon $start, Carbon $end): PayrollPeriod
    {
        return PayrollPeriod::firstOrCreate(
            [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ],
            [
                'week_number' => $start->weekOfYear,
                'status' => 'open',
            ]
        );
    }

    public function assertOpen(Carbon $start, Carbon $end): void
    {
        $period = PayrollPeriod::query()
            ->whereDate('start_date', $start->format('Y-m-d'))
            ->whereDate('end_date', $end->format('Y-m-d'))
            ->first();

        if ($period?->isLocked()) {
            throw ValidationException::withMessages([
                'periodo' => 'Esta semana está cerrada. Desbloquéala antes de modificar o regenerar nóminas.',
            ]);
        }
    }

    public function setLocked(PayrollPeriod $period, bool $locked, User $user, ?string $notes = null): PayrollPeriod
    {
        $period->forceFill([
            'status' => $locked ? 'locked' : 'open',
            'locked_by' => $locked ? $user->id : null,
            'locked_at' => $locked ? now() : null,
            'notes' => $notes,
        ])->save();

        return $period->fresh('lockedBy');
    }
}
