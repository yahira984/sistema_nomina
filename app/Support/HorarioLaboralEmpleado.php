<?php

namespace App\Support;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HorarioLaboralEmpleado
{
    private const VIGILANCIA_RESPALDO = ['20', '29'];

    public static function esVigilancia24x24(Empleado $empleado): bool
    {
        $puesto = Str::upper(Str::ascii(trim((string) ($empleado->puesto ?? ''))));

        return Str::contains($puesto, ['VIGILANCIA', 'SEGURIDAD'])
            || in_array(ReglasNominaEmpleado::numero($empleado), self::VIGILANCIA_RESPALDO, true);
    }

    public static function esDiaLaboral(Empleado $empleado, Carbon|string $fecha): bool
    {
        $dia = $fecha instanceof Carbon
            ? $fecha->copy()->startOfDay()
            : Carbon::parse($fecha)->startOfDay();

        if (!self::estaDentroDeRelacionLaboral($empleado, $dia)) {
            return false;
        }

        if (!self::esVigilancia24x24($empleado)) {
            return !$dia->isWeekend();
        }

        $referencia = self::fechaReferencia24x24($empleado);
        $diferencia = (int) $referencia->diffInDays($dia, false);

        return $diferencia >= 0 && $diferencia % 2 === 0;
    }

    public static function fechasLaborales(Empleado $empleado, Carbon $inicio, Carbon $fin): array
    {
        $fechas = [];
        $cursor = $inicio->copy()->startOfDay();
        $limite = $fin->copy()->startOfDay();

        while ($cursor->lte($limite)) {
            if (self::esDiaLaboral($empleado, $cursor)) {
                $fechas[] = $cursor->format('Y-m-d');
            }

            $cursor->addDay();
        }

        return $fechas;
    }

    private static function fechaReferencia24x24(Empleado $empleado): Carbon
    {
        if ($empleado->fecha_ingreso) {
            return Carbon::parse($empleado->fecha_ingreso)->startOfDay();
        }

        $numero = (int) (ReglasNominaEmpleado::numero($empleado) ?? $empleado->id ?? 0);

        return Carbon::create(2026, 1, 1)->addDays(abs($numero) % 2)->startOfDay();
    }

    private static function estaDentroDeRelacionLaboral(Empleado $empleado, Carbon $fecha): bool
    {
        if ($empleado->fecha_ingreso && $fecha->lt(Carbon::parse($empleado->fecha_ingreso)->startOfDay())) {
            return false;
        }

        return !$empleado->fecha_baja
            || $fecha->lte(Carbon::parse($empleado->fecha_baja)->startOfDay());
    }
}
