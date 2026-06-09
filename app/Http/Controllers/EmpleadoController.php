<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpleadoController extends Controller
{
    public function index()
    {
        $empleados = Empleado::orderByDesc('estatus')->orderBy('id', 'desc')->get();
        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados
        ]);
    }
    // ... tu funcion index() ...

    // 🔥 NUEVA FUNCIÓN PARA EL EXPEDIENTE DIGITAL
    public function show($id)
    {
        // Jalamos al empleado y sus últimos 30 días de asistencia para no saturar
        $empleado = Empleado::with(['asistencias' => function($query) {
            $query->orderBy('fecha', 'desc')->take(30);
        }])->findOrFail($id);

        return Inertia::render('Empleados/Show', [
            'empleado' => $empleado
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
            'curp' => 'nullable|string|max:18',
            'estado_civil' => 'nullable|string|max:50',
            'genero' => 'nullable|string|max:30',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'contacto_emergencia_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'ajuste_vacaciones' => 'nullable|integer', // <-- Validamos el nuevo campo
            'es_estudiante' => 'nullable|boolean',
        ]);

        $datos = $request->all();
        $datos['rfc'] = $request->filled('rfc') ? strtoupper($request->input('rfc')) : null;
        $datos['curp'] = $request->filled('curp') ? strtoupper($request->input('curp')) : null;
        $datos['correo'] = $request->filled('correo') ? strtolower($request->input('correo')) : null;
        
        $datos['saldo_prestamo'] = $request->input('saldo_prestamo', 0) ?: 0;
        $datos['cuota_prestamo'] = $request->input('cuota_prestamo', 0) ?: 0;
        $datos['descuento_imss'] = $request->input('descuento_imss', 0) ?: 0;
        $datos['descuento_isr'] = $request->input('descuento_isr', 0) ?: 0;
        $datos['descuento_infonavit'] = $request->input('descuento_infonavit', 0) ?: 0;
        
        // Guardamos el ajuste (si lo dejan en blanco, le ponemos 0)
        $datos['ajuste_vacaciones'] = $request->input('ajuste_vacaciones', 0) ?: 0;
        $datos['es_estudiante'] = $request->boolean('es_estudiante');

        // 👨‍🎓 LÓGICA DE ESTUDIANTE
        if ($request->boolean('es_estudiante')) {
            $datos['sueldo_semanal'] = 0;
            $datos['sueldo_por_hora'] = $request->input('sueldo_por_hora', 27.00) ?: 27.00;
        } else {
            $datos['sueldo_por_hora'] = 0;
            $datos['sueldo_semanal'] = $request->input('sueldo_semanal', 0) ?: 0;
        }

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
            'curp' => 'nullable|string|max:18',
            'estado_civil' => 'nullable|string|max:50',
            'genero' => 'nullable|string|max:30',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'contacto_emergencia_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'ajuste_vacaciones' => 'nullable|integer', // <-- Validamos el nuevo campo
            'es_estudiante' => 'nullable|boolean',
        ]);

        $datos = $request->all();
        $datos['rfc'] = $request->filled('rfc') ? strtoupper($request->input('rfc')) : null;
        $datos['curp'] = $request->filled('curp') ? strtoupper($request->input('curp')) : null;
        $datos['correo'] = $request->filled('correo') ? strtolower($request->input('correo')) : null;
        
        $datos['saldo_prestamo'] = $request->input('saldo_prestamo', 0) ?: 0;
        $datos['cuota_prestamo'] = $request->input('cuota_prestamo', 0) ?: 0;
        $datos['descuento_imss'] = $request->input('descuento_imss', 0) ?: 0;
        $datos['descuento_isr'] = $request->input('descuento_isr', 0) ?: 0;
        $datos['descuento_infonavit'] = $request->input('descuento_infonavit', 0) ?: 0;

        // Guardamos el ajuste
        $datos['ajuste_vacaciones'] = $request->input('ajuste_vacaciones', 0) ?: 0;
        $datos['es_estudiante'] = $request->boolean('es_estudiante');

        // 👨‍🎓 LÓGICA DE ESTUDIANTE
        if ($request->boolean('es_estudiante')) {
            $datos['sueldo_semanal'] = 0;
            $datos['sueldo_por_hora'] = $request->input('sueldo_por_hora', 27.00) ?: 27.00;
        } else {
            $datos['sueldo_por_hora'] = 0;
            $datos['sueldo_semanal'] = $request->input('sueldo_semanal', 0) ?: 0;
        }

        if ($datos['forma_pago'] === 'Efectivo') {
            $datos['banco'] = null;
            $datos['numero_cuenta'] = null;
        }

        $empleado->update($datos);
        return redirect()->back()->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $fechaBaja = Carbon::now()->startOfDay();
        $fechaIngreso = $empleado->fecha_ingreso ? Carbon::parse($empleado->fecha_ingreso)->startOfDay() : null;
        $diasLaborados = $fechaIngreso ? $fechaIngreso->diffInDays($fechaBaja) + 1 : 0;

        $empleado->update([
            'estatus' => false,
            'numero_empleado_baja' => $empleado->numero_empleado_baja ?: $empleado->numero_empleado,
            'numero_empleado' => null,
            'fecha_baja' => $fechaBaja->format('Y-m-d'),
            'dias_laborados' => $diasLaborados,
            'motivo_baja' => request('motivo_baja'),
        ]);

        return redirect()->back()->with('success', 'Empleado enviado a papelera y numero liberado.');
    }

    public function restaurar(Empleado $empleado)
    {
        $empleado->update([
            'estatus' => true,
            'fecha_baja' => null,
            'dias_laborados' => 0,
            'motivo_baja' => null,
        ]);

        return redirect()->back()->with('success', 'Empleado restaurado. Asignale un numero nuevo o disponible antes de usarlo en checador.');
    }
}
