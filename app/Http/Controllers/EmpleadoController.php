<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Services\FirebaseSyncService;
use App\Support\DiasLaborados;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

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
            'empleado' => $empleado,
            'accesoApp' => FirebaseSyncService::obtenerAccesoApp($empleado),
        ]);
    }

    public function actualizarFechaBaja(Request $request, Empleado $empleado)
{
    $data = $request->validate([
        'fecha_baja' => ['required', 'date', 'before_or_equal:today'],
    ], [
        'fecha_baja.required' => 'La fecha de baja es obligatoria.',
        'fecha_baja.date' => 'La fecha de baja no es válida.',
        'fecha_baja.before_or_equal' => 'La fecha de baja no puede ser futura.',
    ]);

    if ($empleado->estatus) {
        return back()->withErrors([
            'fecha_baja' => 'Este empleado sigue activo. Primero debes darlo de baja.',
        ]);
    }

    if ($empleado->fecha_ingreso) {
        $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
        $fechaBaja = Carbon::parse($data['fecha_baja']);

        if ($fechaBaja->lt($fechaIngreso)) {
            return back()->withErrors([
                'fecha_baja' => 'La fecha de baja no puede ser menor a la fecha de ingreso.',
            ]);
        }
    }

    $empleado->fecha_baja = $data['fecha_baja'];

    if (
        $empleado->fecha_ingreso &&
        Schema::hasColumn('empleados', 'dias_laborados')
    ) {
        $fechaIngreso = Carbon::parse($empleado->fecha_ingreso);
        $fechaBaja = Carbon::parse($data['fecha_baja']);

        $empleado->dias_laborados = DiasLaborados::contarSinDomingos($fechaIngreso, $fechaBaja);

        if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
            $empleado->dias_laborados_anio_baja = DiasLaborados::contarAnioDeBaja($fechaIngreso, $fechaBaja);
        }
    }

    $empleado->save();

    return back()->with('success', 'Fecha de baja actualizada correctamente.');
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

        $empleado = Empleado::create($datos);
        FirebaseSyncService::sincronizarEmpleado($empleado);

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
        FirebaseSyncService::sincronizarEmpleado($empleado);

        return redirect()->back()->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        $fechaBaja = Carbon::now()->startOfDay();
        $fechaIngreso = $empleado->fecha_ingreso ? Carbon::parse($empleado->fecha_ingreso)->startOfDay() : null;
        $diasLaborados = $fechaIngreso ? DiasLaborados::contarSinDomingos($fechaIngreso, $fechaBaja) : 0;
        $diasLaboradosAnioBaja = $fechaIngreso ? DiasLaborados::contarAnioDeBaja($fechaIngreso, $fechaBaja) : 0;
        $this->moverFotoEmpleadoABajas($empleado);

        $datosBaja = [
            'estatus' => false,
            'numero_empleado_baja' => $empleado->numero_empleado_baja ?: $empleado->numero_empleado,
            'numero_empleado' => null,
            'fecha_baja' => $fechaBaja->format('Y-m-d'),
            'dias_laborados' => $diasLaborados,
            'motivo_baja' => request('motivo_baja'),
        ];

        if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
            $datosBaja['dias_laborados_anio_baja'] = $diasLaboradosAnioBaja;
        }

        $empleado->update($datosBaja);

        FirebaseSyncService::desactivarAccesoApp($empleado);
        FirebaseSyncService::sincronizarEmpleado($empleado);

        return redirect()->back()->with('success', 'Empleado enviado a papelera y numero liberado.');
    }

    public function restaurar(Empleado $empleado)
    {
        $datosRestaurar = [
            'estatus' => true,
            'fecha_baja' => null,
            'dias_laborados' => 0,
            'motivo_baja' => null,
        ];

        if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
            $datosRestaurar['dias_laborados_anio_baja'] = 0;
        }

        $empleado->update($datosRestaurar);

        FirebaseSyncService::sincronizarEmpleado($empleado);

        return redirect()->back()->with('success', 'Empleado restaurado. Asignale un numero nuevo o disponible antes de usarlo en checador.');
    }

    public function guardarAccesoApp(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'usuario' => ['required', 'string', 'min:3', 'max:80', 'regex:/^[A-Za-z0-9._@-]+$/'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ], [
            'usuario.regex' => 'Usa solo letras, numeros, punto, guion, guion bajo o correo.',
            'password.min' => 'La contrasena debe tener al menos 6 caracteres.',
        ]);

        $resultado = FirebaseSyncService::guardarAccesoApp(
            $empleado,
            $validated['usuario'],
            $validated['password']
        );

        if (!($resultado['ok'] ?? false)) {
            return back()->withErrors([
                'acceso_app' => $resultado['message'] ?? 'No se pudo guardar el acceso de la app.',
            ]);
        }

        return back()->with('success', 'Acceso de app guardado. Usuario: ' . $resultado['usuario']);
    }

    public function desactivarAccesoApp(Empleado $empleado)
    {
        $resultado = FirebaseSyncService::desactivarAccesoApp($empleado);

        if (!($resultado['ok'] ?? false)) {
            return back()->withErrors([
                'acceso_app' => $resultado['message'] ?? 'No se pudo desactivar el acceso de la app.',
            ]);
        }

        return back()->with('success', 'Acceso de app desactivado para este empleado.');
    }

    private function moverFotoEmpleadoABajas(Empleado $empleado): void
    {
        $directorioActivo = public_path('img/empleados');
        $directorioBajas = $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';

        if (!is_dir($directorioActivo)) {
            return;
        }

        if (!is_dir($directorioBajas)) {
            mkdir($directorioBajas, 0755, true);
        }

        $numero = $this->limpiarClaveFoto($empleado->numero_empleado ?: $empleado->numero_empleado_baja);
        $claves = collect([
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
            $numero,
            ltrim($numero, '0') ?: $numero,
        ])->filter()->unique();

        foreach ($claves as $clave) {
            foreach ($this->extensionesFotoEmpleado() as $extension) {
                $origen = $directorioActivo . DIRECTORY_SEPARATOR . "{$clave}.{$extension}";

                if (!is_file($origen)) {
                    continue;
                }

                $destino = $directorioBajas . DIRECTORY_SEPARATOR . "id-{$empleado->id}.{$extension}";

                if (!@rename($origen, $destino) && @copy($origen, $destino)) {
                    @unlink($origen);
                }
            }
        }
    }

    private function limpiarClaveFoto($valor): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($valor ?? ''));
    }

    private function extensionesFotoEmpleado(): array
    {
        return ['webp', 'jpg', 'jpeg', 'png'];
    }
}
