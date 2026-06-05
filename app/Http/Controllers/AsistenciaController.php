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
        $empleados = Empleado::where('estatus', true)->orderBy('nombre_completo', 'asc')->get();
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
            'tipo_asistencia' => 'required|string',
        ]);

        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia);

        Asistencia::create(array_merge([
            'empleado_id' => $request->empleado_id,
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia, // <-- AQUÍ ESTABA EL BUG, YA ESTÁ CORREGIDO
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        return redirect()->back()->with('success', 'Asistencia registrada con éxito.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|string',
        ]);

        $asistencia = Asistencia::findOrFail($id);
        
        $datosCalculados = $this->calcularHoras($request->fecha, $request->hora_entrada, $request->hora_salida, $request->tipo_asistencia);

        $asistencia->update(array_merge([
            'fecha' => $request->fecha,
            'tipo_asistencia' => $request->tipo_asistencia,
            'hora_entrada' => $request->tipo_asistencia === 'Normal' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_asistencia === 'Normal' ? $request->hora_salida : null,
        ], $datosCalculados));

        return redirect()->back()->with('success', 'Asistencia actualizada.');
    }

    public function destroy($id)
    {
        Asistencia::destroy($id);
        return redirect()->back()->with('success', 'Asistencia eliminada.');
    }

    private function calcularHoras($fecha, $hora_entrada, $hora_salida, $tipo_asistencia)
    {
        $minutos_tarde = 0;
        $horas_normales = 0;
        $horas_extra_diarias = 0;

        if ($tipo_asistencia === 'Normal' && $hora_entrada && $hora_salida) {
            $fecha_carbon = Carbon::parse($fecha);
            $entrada = Carbon::parse($fecha . ' ' . $hora_entrada);
            $salida = Carbon::parse($fecha . ' ' . $hora_salida);
            $hora_oficial = Carbon::parse($fecha . ' 08:00:00');
            
            if ($entrada->greaterThan($hora_oficial)) {
                $minutos_tarde = $hora_oficial->diffInMinutes($entrada);
            }

            if ($fecha_carbon->isSaturday()) {
                $horas_extra_diarias = $entrada->diffInMinutes($salida) / 60;
            } else {
                $limite_normal = Carbon::parse($fecha . ' 17:30:00');
                if ($salida->greaterThan($limite_normal)) {
                    $horas_normales = $entrada->diffInMinutes($limite_normal) / 60;
                    $horas_extra_diarias = $limite_normal->diffInMinutes($salida) / 60;
                } else {
                    $horas_normales = $entrada->diffInMinutes($salida) / 60;
                }
            }
        }

        return [
            'minutos_tarde' => $minutos_tarde,
            'horas_trabajadas' => $horas_normales,
            'horas_extra' => $horas_extra_diarias
        ];
    }
}