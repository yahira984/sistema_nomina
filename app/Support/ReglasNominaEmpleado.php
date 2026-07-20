<?php

namespace App\Support;

use App\Models\Empleado;

class ReglasNominaEmpleado
{
    private const SIN_HORAS_EXTRA = ['8', '9', '22'];
    private const SIN_RETARDOS = ['14', '76', '78'];
    private const PAGO_POR_HORA_TOPADO = ['76', '78'];

    public const TOPE_HORAS_POR_HORA = 48.0;

    public static function sinHorasExtra(Empleado $empleado): bool
    {
        return HorarioLaboralEmpleado::esVigilancia24x24($empleado)
            || self::coincideNumero($empleado, self::SIN_HORAS_EXTRA);
    }

    public static function sinRetardos(Empleado $empleado): bool
    {
        return HorarioLaboralEmpleado::esVigilancia24x24($empleado)
            || self::coincideNumero($empleado, self::SIN_RETARDOS);
    }

    public static function pagoPorHoraTopado(Empleado $empleado): bool
    {
        return self::coincideNumero($empleado, self::PAGO_POR_HORA_TOPADO);
    }

    public static function numero(Empleado $empleado): ?string
    {
        return self::normalizarNumero($empleado->numero_empleado ?? $empleado->numero_empleado_baja ?? null);
    }

    private static function coincideNumero(Empleado $empleado, array $numeros): bool
    {
        $numero = self::numero($empleado);

        return $numero !== null && in_array($numero, $numeros, true);
    }

    private static function normalizarNumero($numero): ?string
    {
        $normalizado = trim((string) $numero);

        if ($normalizado === '') {
            return null;
        }

        $normalizado = ltrim($normalizado, '0');

        return $normalizado === '' ? '0' : $normalizado;
    }
}
