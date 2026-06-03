<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    public function index()
    {
        $empleados = Empleado::where('estatus', true)->get();
        $asistencias = Asistencia::with('empleado')->orderBy('fecha', 'desc')->get();
        
        return Inertia::render('Asistencias/Index', [
            'empleados' => $empleados,
            'asistencias' => $asistencias
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fecha' => 'required|date',
            'hora_entrada' => 'required',
            'hora_salida' => 'required',
        ]);

        $entrada = Carbon::parse($request->hora_entrada);
        $salida = Carbon::parse($request->hora_salida);
        $horas_trabajadas = $entrada->diffInMinutes($salida) / 60;

        Asistencia::create([
            'empleado_id' => $request->empleado_id,
            'fecha' => $request->fecha,
            'hora_entrada' => $request->hora_entrada,
            'hora_salida' => $request->hora_salida,
            'horas_trabajadas' => round($horas_trabajadas, 2),
        ]);

        return redirect()->back();
    }

    // NUEVO: Función para actualizar
    public function update(Request $request, Asistencia $asistencia)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora_entrada' => 'required',
            'hora_salida' => 'required',
        ]);

        // Volvemos a calcular por si le corrigieron la hora
        $entrada = Carbon::parse($request->hora_entrada);
        $salida = Carbon::parse($request->hora_salida);
        $horas_trabajadas = $entrada->diffInMinutes($salida) / 60;

        $asistencia->update([
            'fecha' => $request->fecha,
            'hora_entrada' => $request->hora_entrada,
            'hora_salida' => $request->hora_salida,
            'horas_trabajadas' => round($horas_trabajadas, 2),
        ]);

        return redirect()->back();
    }

    // NUEVO: Función para eliminar
    public function destroy(Asistencia $asistencia)
    {
        $asistencia->delete();
        return redirect()->back();
    }
}