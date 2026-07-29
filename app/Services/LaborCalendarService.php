<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\LaborCalendarDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LaborCalendarService
{
    private static array $cache = [];

    public static function overrideFor(Empleado $empleado, Carbon|string $date): ?bool
    {
        if (!Schema::hasTable('labor_calendar_days')) {
            return null;
        }

        $date = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        $cacheKey = $empleado->id . '|' . $date . '|' . $empleado->puesto;

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $position = Str::upper(Str::ascii(trim((string) $empleado->puesto)));
        $day = LaborCalendarDay::query()
            ->whereDate('date', $date)
            ->where('active', true)
            ->where(function ($query) use ($empleado, $position) {
                $query->where('scope_type', 'global')
                    ->orWhere(fn ($query) => $query
                        ->where('scope_type', 'employee')
                        ->where('empleado_id', $empleado->id));

                if ($position !== '') {
                    $query->orWhere(fn ($query) => $query
                        ->where('scope_type', 'position')
                        ->whereRaw('UPPER(position) = ?', [$position]));
                }
            })
            ->orderByRaw("CASE scope_type WHEN 'employee' THEN 1 WHEN 'position' THEN 2 ELSE 3 END")
            ->first();

        if (!$day) {
            return self::$cache[$cacheKey] = null;
        }

        return self::$cache[$cacheKey] = $day->kind === 'working';
    }

    public static function forget(): void
    {
        self::$cache = [];
    }
}
