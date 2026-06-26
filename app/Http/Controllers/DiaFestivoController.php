<?php

namespace App\Http\Controllers;

use App\Models\DiaFestivo;
use App\Services\DiasFestivosMexicoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DiaFestivoController extends Controller
{
    public function index(Request $request, DiasFestivosMexicoService $festivosMexico)
    {
        $anio = (int) $request->input('anio', now()->year);
        $anio = max(2024, min(2100, $anio));

        $festivosMexico->sincronizarRango($anio - 1, $anio + 1);

        $dias = DiaFestivo::whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();

        return Inertia::render('Sistema/DiasFestivos', [
            'anio' => $anio,
            'aniosDisponibles' => collect(range(now()->year - 1, now()->year + 5))
                ->push($anio)
                ->unique()
                ->sort()
                ->values(),
            'diasFestivos' => $dias,
            'estadisticas' => [
                'total' => $dias->count(),
                'activos' => $dias->where('activo', true)->count(),
                'oficiales' => $dias->where('es_oficial', true)->count(),
                'manuales' => $dias->where('origen', 'manual')->count(),
            ],
            'fuenteOficial' => 'Ley Federal del Trabajo, articulo 74. Las jornadas electorales se agregan manualmente cuando aplique.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validarDiaFestivo($request);

        DiaFestivo::create(array_merge($validated, [
            'fecha' => Carbon::parse($validated['fecha'])->format('Y-m-d'),
            'tipo' => $validated['tipo'] ?? 'manual',
            'origen' => 'manual',
        ]));

        return back()->with('success', 'Dia festivo agregado correctamente.');
    }

    public function update(Request $request, DiaFestivo $diaFestivo)
    {
        $validated = $this->validarDiaFestivo($request, $diaFestivo);

        $diaFestivo->forceFill(array_merge($validated, [
            'fecha' => Carbon::parse($validated['fecha'])->format('Y-m-d'),
            'tipo' => $validated['tipo'] ?? $diaFestivo->tipo,
            'origen' => 'manual',
        ]))->save();

        return back()->with('success', 'Dia festivo actualizado correctamente.');
    }

    public function destroy(DiaFestivo $diaFestivo)
    {
        $diaFestivo->forceFill([
            'activo' => false,
            'origen' => 'manual',
        ])->save();

        return back()->with('success', 'Dia festivo desactivado. Puedes volver a activarlo editandolo.');
    }

    public function generar(Request $request, DiasFestivosMexicoService $festivosMexico)
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2024', 'max:2100'],
        ]);

        $creados = $festivosMexico->sincronizarAnio((int) $validated['anio']);

        return back()->with('success', $creados > 0
            ? "{$creados} dia(s) festivo(s) oficial(es) generados."
            : 'El año ya tiene sus festivos oficiales generados.'
        );
    }

    private function validarDiaFestivo(Request $request, ?DiaFestivo $diaFestivo = null): array
    {
        return $request->validate([
            'fecha' => [
                'required',
                'date',
                Rule::unique('dias_festivos', 'fecha')->ignore($diaFestivo?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['oficial', 'empresa', 'manual', 'electoral'])],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'es_oficial' => ['required', 'boolean'],
            'activo' => ['required', 'boolean'],
        ]);
    }
}
