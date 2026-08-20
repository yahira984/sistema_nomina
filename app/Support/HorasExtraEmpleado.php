<?php

namespace App\Support;

use App\Models\Empleado;
use App\Services\WorkRuleResolver;
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
        return self::detalle($empleado, $fecha, $horaEntrada, $horaSalida)['horas'];
    }

    public static function detalle(
        ?Empleado $empleado,
        Carbon|string $fecha,
        ?string $horaEntrada,
        ?string $horaSalida
    ): array {
        if (!$horaEntrada || !$horaSalida) {
            return ['horas' => 0.0, 'minutos_tolerancia' => 0];
        }

        if ($empleado && ReglasNominaEmpleado::sinHorasExtra($empleado)) {
            return ['horas' => 0.0, 'minutos_tolerancia' => 0];
        }

        $dia = $fecha instanceof Carbon
            ? $fecha->copy()->startOfDay()
            : Carbon::parse($fecha)->startOfDay();
        $rule = $empleado ? WorkRuleResolver::for($empleado) : [];
        $entrada = Carbon::parse($dia->format('Y-m-d').' '.$horaEntrada);
        $salida = Carbon::parse($dia->format('Y-m-d').' '.$horaSalida);

        if ($salida->lessThanOrEqualTo($entrada)) {
            return ['horas' => 0.0, 'minutos_tolerancia' => 0];
        }

        if ($dia->isWeekend()) {
            $horaInicio = Carbon::parse($dia->format('Y-m-d').' '.($rule['hora_entrada'] ?? self::HORA_INICIO));
            $inicioExtra = $entrada->lessThan($horaInicio) ? $horaInicio : $entrada;

            return self::redondearMinutosConTolerancia($inicioExtra->diffInMinutes($salida));
        }

        $horario = $empleado
            ? JornadaLaboralEmpleado::horario($empleado, $dia)
            : ['salida' => self::HORA_FIN_ORDINARIA];
        $limiteOrdinario = Carbon::parse($dia->format('Y-m-d').' '.$horario['salida']);

        if (!$salida->greaterThan($limiteOrdinario)) {
            return ['horas' => 0.0, 'minutos_tolerancia' => 0];
        }

        return self::redondearMinutosConTolerancia($limiteOrdinario->diffInMinutes($salida));
    }

    public static function redondearMinutosConTolerancia(int $minutos): array
    {
        $minutos = max(0, $minutos);
        $bloquesCompletos = intdiv($minutos, 30);
        $residuo = $minutos % 30;
        $faltantes = $residuo === 0 ? 0 : 30 - $residuo;
        $minutosTolerancia = $faltantes >= 1 && $faltantes <= 7 ? $faltantes : 0;

        return [
            'horas' => ($bloquesCompletos + ($minutosTolerancia > 0 ? 1 : 0)) / 2,
            'minutos_tolerancia' => $minutosTolerancia,
        ];
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
