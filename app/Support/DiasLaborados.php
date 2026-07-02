<?php

namespace App\Support;

use Carbon\Carbon;

class DiasLaborados
{
    public static function contarSinDomingos($fechaInicio, $fechaFin): int
    {
        if (!$fechaInicio || !$fechaFin) {
            return 0;
        }

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        if ($fin->lt($inicio)) {
            return 0;
        }

        $dias = 0;
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            if (!$cursor->isSunday()) {
                $dias++;
            }

            $cursor->addDay();
        }

        return $dias;
    }

    public static function contarAnioDeBaja($fechaInicio, $fechaBaja): int
    {
        if (!$fechaInicio || !$fechaBaja) {
            return 0;
        }

        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $baja = Carbon::parse($fechaBaja)->startOfDay();

        if ($baja->lt($inicio)) {
            return 0;
        }

        $inicioAnioBaja = $baja->copy()->startOfYear();
        $inicioConteo = $inicio->gt($inicioAnioBaja) ? $inicio : $inicioAnioBaja;

        return self::contarSinDomingos($inicioConteo, $baja);
    }
}
