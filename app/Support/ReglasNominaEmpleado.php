<?php

namespace App\Support;

use App\Models\Empleado;
use App\Services\WorkRuleResolver;

class ReglasNominaEmpleado
{
    public const TOPE_HORAS_POR_HORA = 48.0;

    public static function sinHorasExtra(Empleado $empleado): bool
    {
        return (bool) WorkRuleResolver::for($empleado)['sin_horas_extra'];
    }

    public static function sinRetardos(Empleado $empleado): bool
    {
        return (bool) WorkRuleResolver::for($empleado)['sin_retardos'];
    }

    public static function pagoPorHoraTopado(Empleado $empleado): bool
    {
        return (bool) WorkRuleResolver::for($empleado)['pago_por_hora_topado'];
    }

    public static function turno24x24(Empleado $empleado): bool
    {
        return (bool) WorkRuleResolver::for($empleado)['turno_24x24'];
    }

    public static function topeHorasSemanales(Empleado $empleado): float
    {
        return (float) (WorkRuleResolver::for($empleado)['tope_horas_semanales'] ?? self::TOPE_HORAS_POR_HORA);
    }

    public static function configuracion(Empleado $empleado): array
    {
        return WorkRuleResolver::for($empleado);
    }

    public static function numero(Empleado $empleado): ?string
    {
        return WorkRuleResolver::employeeNumber($empleado);
    }
}
