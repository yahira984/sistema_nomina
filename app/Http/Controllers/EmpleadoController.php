<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpleadoController extends Controller
{
    public function index()
    {
        $empleados = Empleado::orderBy('id', 'desc')->get();
        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_empleado' => 'nullable|string|unique:empleados,numero_empleado',
            'nombre_completo' => 'required|string|max:255',
            'puesto' => 'nullable|string|max:255',
            'sueldo_por_hora' => 'required|numeric|min:0',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:50',
            'rfc' => 'nullable|string|max:50',
        ]);

        Empleado::create($request->all());
        return redirect()->back();
    }

    // NUEVO: Función para actualizar
    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            // Ignoramos el unique si es el mismo empleado
            'numero_empleado' => 'nullable|string|unique:empleados,numero_empleado,'.$empleado->id,
            'nombre_completo' => 'required|string|max:255',
            'puesto' => 'nullable|string|max:255',
            'sueldo_por_hora' => 'required|numeric|min:0',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:20',
            'nss' => 'nullable|string|max:50',
            'rfc' => 'nullable|string|max:50',
        ]);

        $empleado->update($request->all());
        return redirect()->back();
    }

    // NUEVO: Función para eliminar
    public function destroy(Empleado $empleado)
    {
        $empleado->delete();
        return redirect()->back();
    }
}