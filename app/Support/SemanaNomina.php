<?php

namespace App\Support;

use Carbon\Carbon;

class SemanaNomina
{
    public static function corteActual(?Carbon $fecha = null): Carbon
    {
        $base = ($fecha ?? Carbon::now())->copy();

        return $base->isWednesday()
            ? $base->endOfDay()
            : $base->previous(Carbon::WEDNESDAY)->endOfDay();
    }

    public static function desdeCorte(?string $fechaCorte = null): array
    {
        $fin = $fechaCorte
            ? Carbon::parse($fechaCorte)->endOfDay()
            : self::corteActual();
        $inicio = $fin->copy()->subDays(6)->startOfDay();

        return [$inicio, $fin, $inicio->weekOfYear];
    }

    public static function disponibles(Carbon $corteBase, int $cantidad = 10): array
    {
        $semanas = [];
        $iterador = $corteBase->copy();

        for ($i = 0; $i < $cantidad; $i++) {
            [$inicio, $fin, $numeroSemana] = self::desdeCorte($iterador->format('Y-m-d'));
            $semanas[] = [
                'fecha_corte' => $fin->format('Y-m-d'),
                'numero_semana' => $numeroSemana,
                'etiqueta' => 'Sem. ' . $numeroSemana . ' (' . $inicio->locale('es')->isoFormat('D MMM YYYY') . ' al ' . $fin->locale('es')->isoFormat('D MMM YYYY') . ')',
            ];

            $iterador->subWeek();
        }

        return $semanas;
    }
}
