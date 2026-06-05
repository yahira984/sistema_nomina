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
            
            'sueldo_por_hora' => 'required|numeric|min:1',
            'saldo_prestamo' => 'nullable|numeric|min:0',
            'cuota_prestamo' => 'nullable|numeric|min:0',
            'cuota_seguro' => 'nullable|numeric|min:0',
            
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|digits_between:10,20',
            'nss' => 'nullable|string|digits:11',
            'rfc' => 'nullable|string|min:12|max:13',
        ], [
            'nss.digits' => 'El Número de Seguro Social debe tener exactamente 11 dígitos numéricos.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'numero_cuenta.digits_between' => 'El número de cuenta debe tener entre 10 y 20 números.',
            'numero_empleado.unique' => 'Ese número de empleado ya está asignado a otra persona.',
        ]);

        // Atrapamos los datos antes de guardarlos
        $datos = $request->all();
        
        // Si vienen vacíos (null), los forzamos a ser 0
        $datos['saldo_prestamo'] = $datos['saldo_prestamo'] ?: 0;
        $datos['cuota_prestamo'] = $datos['cuota_prestamo'] ?: 0;
        $datos['cuota_seguro'] = $datos['cuota_seguro'] ?: 0;

        Empleado::create($datos);
        
        return redirect()->back()->with('success', 'Empleado registrado correctamente.');
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            'numero_empleado' => 'nullable|string|unique:empleados,numero_empleado,'.$empleado->id,
            'nombre_completo' => 'required|string|max:255',
            'puesto' => 'nullable|string|max:255',
            
            'sueldo_por_hora' => 'required|numeric|min:1',
            'saldo_prestamo' => 'nullable|numeric|min:0',
            'cuota_prestamo' => 'nullable|numeric|min:0',
            'cuota_seguro' => 'nullable|numeric|min:0',
            
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|digits_between:10,20',
            'nss' => 'nullable|string|digits:11',
            'rfc' => 'nullable|string|min:12|max:13',
        ], [
            'nss.digits' => 'El Número de Seguro Social debe tener exactamente 11 dígitos numéricos.',
            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'numero_cuenta.digits_between' => 'El número de cuenta debe tener entre 10 y 20 números.',
            'numero_empleado.unique' => 'Ese número de empleado ya está asignado a otra persona.',
        ]);

        // Atrapamos los datos antes de guardarlos
        $datos = $request->all();
        
        // Si vienen vacíos (null), los forzamos a ser 0
        $datos['saldo_prestamo'] = $datos['saldo_prestamo'] ?: 0;
        $datos['cuota_prestamo'] = $datos['cuota_prestamo'] ?: 0;
        $datos['cuota_seguro'] = $datos['cuota_seguro'] ?: 0;

        $empleado->update($datos);
        
        return redirect()->back()->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->delete();
        return redirect()->back()->with('success', 'Empleado eliminado correctamente.');
    }
}