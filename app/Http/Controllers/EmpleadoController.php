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
            'fecha_ingreso' => 'nullable|date',
            'forma_pago' => 'required|string|in:Efectivo,Deposito', 
            
            'banco' => 'nullable|required_if:forma_pago,Deposito|string|max:100',
            'numero_cuenta' => 'nullable|required_if:forma_pago,Deposito|string|max:25',
            'nss' => 'nullable|string|max:20',
            'rfc' => 'nullable|string|max:20',
        ]);

        $datos = $request->all();
        
        $datos['saldo_prestamo'] = $request->input('saldo_prestamo', 0) ?: 0;
        $datos['cuota_prestamo'] = $request->input('cuota_prestamo', 0) ?: 0;
        $datos['descuento_imss'] = $request->input('descuento_imss', 0) ?: 0;
        $datos['descuento_isr'] = $request->input('descuento_isr', 0) ?: 0;
        $datos['descuento_infonavit'] = $request->input('descuento_infonavit', 0) ?: 0;

        // 👨‍🎓 LÓGICA DE ESTUDIANTE
        if ($request->boolean('es_estudiante')) {
            $datos['sueldo_semanal'] = 0;
            $datos['sueldo_por_hora'] = $request->input('sueldo_por_hora', 27.00) ?: 27.00;
        } else {
            $datos['sueldo_por_hora'] = 0;
            $datos['sueldo_semanal'] = $request->input('sueldo_semanal', 0) ?: 0;
        }

        // Borramos esta llave para que MySQL no explote
        unset($datos['es_estudiante']);

        // Limpiamos la basura si le pagan en efectivo
        if ($datos['forma_pago'] === 'Efectivo') {
            $datos['banco'] = null;
            $datos['numero_cuenta'] = null;
        }

        Empleado::create($datos);
        return redirect()->back()->with('success', 'Empleado registrado correctamente.');
    }

    public function update(Request $request, Empleado $empleado)
    {
        $request->validate([
            'numero_empleado' => 'nullable|string|unique:empleados,numero_empleado,'.$empleado->id,
            'nombre_completo' => 'required|string|max:255',
            'puesto' => 'nullable|string|max:255',
            'fecha_ingreso' => 'nullable|date',
            'forma_pago' => 'required|string|in:Efectivo,Deposito',
            
            'banco' => 'nullable|required_if:forma_pago,Deposito|string|max:100',
            'numero_cuenta' => 'nullable|required_if:forma_pago,Deposito|string|max:25',
            'nss' => 'nullable|string|max:20',
            'rfc' => 'nullable|string|max:20',
        ]);

        $datos = $request->all();
        
        $datos['saldo_prestamo'] = $request->input('saldo_prestamo', 0) ?: 0;
        $datos['cuota_prestamo'] = $request->input('cuota_prestamo', 0) ?: 0;
        $datos['descuento_imss'] = $request->input('descuento_imss', 0) ?: 0;
        $datos['descuento_isr'] = $request->input('descuento_isr', 0) ?: 0;
        $datos['descuento_infonavit'] = $request->input('descuento_infonavit', 0) ?: 0;

        // 👨‍🎓 LÓGICA DE ESTUDIANTE
        if ($request->boolean('es_estudiante')) {
            $datos['sueldo_semanal'] = 0;
            $datos['sueldo_por_hora'] = $request->input('sueldo_por_hora', 27.00) ?: 27.00;
        } else {
            $datos['sueldo_por_hora'] = 0;
            $datos['sueldo_semanal'] = $request->input('sueldo_semanal', 0) ?: 0;
        }

        // Borramos esta llave para que MySQL no explote
        unset($datos['es_estudiante']);

        if ($datos['forma_pago'] === 'Efectivo') {
            $datos['banco'] = null;
            $datos['numero_cuenta'] = null;
        }

        $empleado->update($datos);
        return redirect()->back()->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->delete();
        return redirect()->back()->with('success', 'Empleado eliminado correctamente.');
    }
}