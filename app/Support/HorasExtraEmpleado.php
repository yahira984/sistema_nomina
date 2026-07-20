<?php

namespace App\Support;

use App\Models\Empleado;
use Carbon\Carbon;

class HorasExtraEmpleado
{
    private const HORA_INICIO = '08:00:00';

    private const HORA_FIN_ORDINARIA = '17:30:00';

    public static function calcular(
        ?Empleado $empleado,
        Carbon|string $fecha,
        ?string $horaEntrada,
        ?string $horaSalida
    ): float {
        if (!$horaEntrada || !$horaSalida) {
            return 0;
        }

        if ($empleado && ReglasNominaEmpleado::sinHorasExtra($empleado)) {
            return 0;
        }

        $dia = $fecha instanceof Carbon
            ? $fecha->copy()->startOfDay()
            : Carbon::parse($fecha)->startOfDay();
        $entrada = Carbon::parse($dia->format('Y-m-d').' '.$horaEntrada);
        $salida = Carbon::parse($dia->format('Y-m-d').' '.$horaSalida);

        if ($salida->lessThanOrEqualTo($entrada)) {
            return 0;
        }

        if ($dia->isWeekend()) {
            $horaInicio = Carbon::parse($dia->format('Y-m-d').' '.self::HORA_INICIO);
            $inicioExtra = $entrada->lessThan($horaInicio) ? $horaInicio : $entrada;

            return self::redondearMediaHoraCercana($inicioExtra->diffInMinutes($salida) / 60);
        }

        $limiteOrdinario = Carbon::parse($dia->format('Y-m-d').' '.self::HORA_FIN_ORDINARIA);

        if (!$salida->greaterThan($limiteOrdinario)) {
            return 0;
        }

        return self::redondearMediaHoraInferior($limiteOrdinario->diffInMinutes($salida) / 60);
    }

    public static function redondearMediaHoraInferior(float $horas): float
    {
        return max(0, floor($horas * 2) / 2);
    }

    public static function redondearMediaHoraCercana(float $horas): float
    {
        return max(0, round($horas * 2) / 2);
    }
}
