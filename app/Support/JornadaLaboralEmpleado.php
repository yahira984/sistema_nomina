<?php

namespace App\Support;

use App\Models\Empleado;
use App\Services\WorkRuleResolver;
use Carbon\Carbon;

class JornadaLaboralEmpleado
{
    public static function horario(Empleado $empleado, Carbon|string|null $fecha = null): array
    {
        $rule = WorkRuleResolver::for($empleado);

        $dia = $fecha === null ? null : ($fecha instanceof Carbon ? $fecha : Carbon::parse($fecha));
        $salida = $dia?->isThursday()
            ? ($rule['hora_salida_jueves'] ?? $rule['hora_salida'] ?? '17:30:00')
            : ($rule['hora_salida'] ?? '17:30:00');

        return [
            'entrada' => (string) ($rule['hora_entrada'] ?? '08:00:00'),
            'salida' => (string) $salida,
        ];
    }

    public static function minutosIncidencia(
        Empleado $empleado,
        Carbon|string $fecha,
        ?string $horaEntrada,
        ?string $horaSalida
    ): int {
        if (!$horaEntrada || !$horaSalida
            || (bool) ($empleado->es_estudiante ?? false)
            || ReglasNominaEmpleado::sinRetardos($empleado)
            || HorarioLaboralEmpleado::esVigilancia24x24($empleado)) {
            return 0;
        }

        $dia = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();

        $ajusteExtra = HorasExtraEmpleado::detalle($empleado, $dia, $horaEntrada, $horaSalida);

        if (!HorarioLaboralEmpleado::esDiaLaboral($empleado, $dia)) {
            return (int) $ajusteExtra['minutos_tolerancia'];
        }

        $horario = self::horario($empleado, $dia);
        $entrada = Carbon::parse($dia->format('Y-m-d').' '.$horaEntrada);
        $salida = Carbon::parse($dia->format('Y-m-d').' '.$horaSalida);

        if ($salida->lessThanOrEqualTo($entrada)) {
            return 0;
        }

        $entradaEsperada = Carbon::parse($dia->format('Y-m-d').' '.$horario['entrada']);
        $salidaEsperada = Carbon::parse($dia->format('Y-m-d').' '.$horario['salida']);
        $minutosEntrada = $entrada->greaterThan($entradaEsperada)
            ? $entradaEsperada->diffInMinutes($entrada)
            : 0;
        $minutosSalida = $salida->lessThan($salidaEsperada)
            ? $salida->diffInMinutes($salidaEsperada)
            : 0;

        return (int) ($minutosEntrada + $minutosSalida + $ajusteExtra['minutos_tolerancia']);
    }

    public static function esDescansoTrabajado24x24(
        Empleado $empleado,
        Carbon|string $fecha,
        ?string $horaEntrada,
        ?string $horaSalida
    ): bool {
        if (!HorarioLaboralEmpleado::esVigilancia24x24($empleado) || !$horaEntrada || !$horaSalida) {
            return false;
        }

        $dia = $fecha instanceof Carbon ? $fecha->copy()->startOfDay() : Carbon::parse($fecha)->startOfDay();
        $entrada = Carbon::parse($dia->format('Y-m-d').' '.$horaEntrada);
        $salida = Carbon::parse($dia->format('Y-m-d').' '.$horaSalida);

        if ($salida->lessThanOrEqualTo($entrada)) {
            $salida->addDay();
        }

        return $salida->greaterThan($entrada) && !HorarioLaboralEmpleado::esDiaLaboral($empleado, $dia);
    }
}
