<?php

namespace App\Services;

use App\Models\DiaFestivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiasFestivosMexicoService
{
    public function sincronizarRango(int $anioInicio, int $anioFin): int
    {
        $creados = 0;

        for ($anio = $anioInicio; $anio <= $anioFin; $anio++) {
            $creados += $this->sincronizarAnio($anio);
        }

        return $creados;
    }

    public function sincronizarAnio(int $anio): int
    {
        $creados = 0;

        foreach ($this->festivosOficiales($anio) as $festivo) {
            $registro = DiaFestivo::firstOrCreate(
                ['fecha' => $festivo['fecha']],
                [
                    'nombre' => $festivo['nombre'],
                    'tipo' => 'oficial',
                    'es_oficial' => true,
                    'activo' => true,
                    'origen' => 'sistema',
                    'descripcion' => $festivo['descripcion'] ?? null,
                ]
            );

            if ($registro->wasRecentlyCreated) {
                $creados++;
            }
        }

        return $creados;
    }

    public function festivosOficiales(int $anio): Collection
    {
        $festivos = collect([
            [
                'fecha' => Carbon::create($anio, 1, 1)->format('Y-m-d'),
                'nombre' => 'Año Nuevo',
                'descripcion' => 'Descanso obligatorio: 1 de enero.',
            ],
            [
                'fecha' => $this->primerLunes($anio, 2)->format('Y-m-d'),
                'nombre' => 'Constitucion Mexicana',
                'descripcion' => 'Primer lunes de febrero en conmemoracion del 5 de febrero.',
            ],
            [
                'fecha' => $this->tercerLunes($anio, 3)->format('Y-m-d'),
                'nombre' => 'Natalicio de Benito Juarez',
                'descripcion' => 'Tercer lunes de marzo en conmemoracion del 21 de marzo.',
            ],
            [
                'fecha' => Carbon::create($anio, 5, 1)->format('Y-m-d'),
                'nombre' => 'Dia del Trabajo',
                'descripcion' => 'Descanso obligatorio: 1 de mayo.',
            ],
            [
                'fecha' => Carbon::create($anio, 9, 16)->format('Y-m-d'),
                'nombre' => 'Independencia de Mexico',
                'descripcion' => 'Descanso obligatorio: 16 de septiembre.',
            ],
            [
                'fecha' => $this->tercerLunes($anio, 11)->format('Y-m-d'),
                'nombre' => 'Revolucion Mexicana',
                'descripcion' => 'Tercer lunes de noviembre en conmemoracion del 20 de noviembre.',
            ],
            [
                'fecha' => Carbon::create($anio, 12, 25)->format('Y-m-d'),
                'nombre' => 'Navidad',
                'descripcion' => 'Descanso obligatorio: 25 de diciembre.',
            ],
        ]);

        if ($this->esAnioTransmisionPoderEjecutivo($anio)) {
            $festivos->push([
                'fecha' => Carbon::create($anio, 10, 1)->format('Y-m-d'),
                'nombre' => 'Transmision del Poder Ejecutivo Federal',
                'descripcion' => 'Descanso obligatorio cada seis anos cuando corresponda la transmision del Poder Ejecutivo Federal.',
            ]);
        }

        return $festivos->sortBy('fecha')->values();
    }

    private function primerLunes(int $anio, int $mes): Carbon
    {
        $fecha = Carbon::create($anio, $mes, 1)->startOfDay();

        while (!$fecha->isMonday()) {
            $fecha->addDay();
        }

        return $fecha;
    }

    private function tercerLunes(int $anio, int $mes): Carbon
    {
        return $this->primerLunes($anio, $mes)->addWeeks(2);
    }

    private function esAnioTransmisionPoderEjecutivo(int $anio): bool
    {
        return $anio >= 2024 && (($anio - 2024) % 6) === 0;
    }
}
