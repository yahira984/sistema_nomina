<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\AuditLog;
use App\Services\FirebaseSyncService;
use App\Services\FirebaseJobDispatcher;
use App\Models\UserPreference;
use App\Support\DiasLaborados;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $savedFilters = Schema::hasTable('user_preferences')
            ? UserPreference::where('user_id', $request->user()?->id)->first()?->saved_filters ?? []
            : [];
        $search = trim((string) $request->input('search', data_get($savedFilters, 'employees.search', '')));
        $status = (string) $request->input('status', data_get($savedFilters, 'employees.status', 'activos'));
        $sort = (string) $request->input('sort', data_get($savedFilters, 'employees.sort', 'num_asc'));
        $perPage = max(12, min(60, (int) $request->input('per_page', 24)));
        $query = Empleado::query()
            ->withCount([
                'asistencias as vacaciones_capturadas_count' => fn ($query) => $query
                    ->where('tipo_asistencia', 'Vacaciones')
                    ->whereRaw('asistencias.fecha >= COALESCE(empleados.fecha_reingreso, empleados.fecha_ingreso)'),
            ])
            ->withSum([
                'nominas as vacaciones_pagadas_sum' => fn ($query) => $query
                    ->where('pagado', true)
                    ->whereRaw('nominas.fecha_fin >= COALESCE(empleados.fecha_reingreso, empleados.fecha_ingreso)'),
            ], 'dias_vacaciones_pagadas')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('numero_empleado', 'like', "%{$search}%")
                        ->orWhere('numero_empleado_baja', 'like', "%{$search}%")
                        ->orWhere('puesto', 'like', "%{$search}%");
                });
            })
            ->when($status === 'activos', fn ($query) => $query->where('estatus', true))
            ->when(in_array($status, ['bajas', 'papelera'], true), fn ($query) => $query->where('estatus', false))
            ->when($status === 'prestamo', fn ($query) => $query->where('estatus', true)->where('saldo_prestamo', '>', 0));

        match ($sort) {
            'name_asc' => $query->orderBy('nombre_completo'),
            'name_desc' => $query->orderByDesc('nombre_completo'),
            'num_desc' => $query
                ->orderByDesc('estatus')
                ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) DESC"),
            default => $query
                ->orderByDesc('estatus')
                ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC"),
        };

        $paginator = $query->paginate($perPage)->withQueryString();
        $employees = $paginator->getCollection()->map(fn (Empleado $employee) => $this->directoryPayload($employee));

        return Inertia::render('Empleados/Index', [
            'empleados' => $employees,
            'empleadosMeta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'resumen' => [
                'activos' => Empleado::where('estatus', true)->count(),
                'bajas' => Empleado::where('estatus', false)->count(),
                'con_deuda' => Empleado::where('estatus', true)->where('saldo_prestamo', '>', 0)->count(),
                'sin_numero' => Empleado::where('estatus', true)->whereNull('numero_empleado')->count(),
            ],
            'filtros' => compact('search', 'status', 'sort'),
        ]);
    }
    // ... tu funcion index() ...

    // 🔥 NUEVA FUNCIÓN PARA EL EXPEDIENTE DIGITAL
    public function show($id)
    {
        // Jalamos al empleado y sus últimos 30 días de asistencia para no saturar
        $empleado = Empleado::with([
            'asistencias' => function ($query) {
                $query->orderBy('fecha', 'desc')->take(30);
            },
            'reingresos.usuarioRegistro:id,name',
        ])->findOrFail($id);

        return Inertia::render('Empleados/Show', [
            'empleado' => $empleado,
            'accesoApp' => Inertia::defer(
                fn () => FirebaseSyncService::obtenerAccesoApp($empleado),
                'perfil-remoto'
            ),
            'timeline' => Inertia::defer(fn () => AuditLog::query()
                ->where('auditable_type', Empleado::class)
                ->where('auditable_id', (string) $empleado->id)
                ->with('user:id,name')
                ->latest('created_at')
                ->limit(100)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'created_at' => $log->created_at?->toISOString(),
                    'user' => $log->user?->name,
                ]), 'perfil-remoto'),
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

    if ($empleado->inicioPeriodoActual()) {
        $fechaIngreso = $empleado->inicioPeriodoActual();
        $fechaBaja = Carbon::parse($data['fecha_baja']);

        if ($fechaBaja->lt($fechaIngreso)) {
            return back()->withErrors([
                'fecha_baja' => 'La fecha de baja no puede ser menor a la fecha de ingreso.',
            ]);
        }
    }

    $empleado->fecha_baja = $data['fecha_baja'];

    if (
        $empleado->inicioPeriodoActual() &&
        Schema::hasColumn('empleados', 'dias_laborados')
    ) {
        $fechaIngreso = $empleado->inicioPeriodoActual();
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

        $datos = $request->except(['fecha_reingreso', 'fecha_baja', 'estatus']);
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
        FirebaseJobDispatcher::employee($empleado);

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

        $numeroEmpleadoAnterior = $empleado->numero_empleado ?: $empleado->numero_empleado_baja;
        $datos = $request->except(['fecha_reingreso', 'fecha_baja', 'estatus']);
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
        $this->renombrarFotoAlNumeroActual($empleado, $numeroEmpleadoAnterior);
        FirebaseJobDispatcher::employee($empleado);

        return redirect()->back()->with('success', 'Datos del empleado actualizados correctamente.');
    }

    public function destroy(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'fecha_baja' => ['required', 'date', 'before_or_equal:today'],
            'motivo_baja' => ['nullable', 'string', 'max:500'],
        ], [
            'fecha_baja.required' => 'Indica la fecha efectiva de baja.',
            'fecha_baja.before_or_equal' => 'La fecha de baja no puede ser futura.',
        ]);

        if (!$empleado->estatus) {
            return back()->withErrors(['fecha_baja' => 'Este empleado ya se encuentra dado de baja.']);
        }

        $fechaBaja = Carbon::parse($validated['fecha_baja'])->startOfDay();
        $fechaIngreso = $empleado->inicioPeriodoActual();

        if ($fechaIngreso && $fechaBaja->lt($fechaIngreso)) {
            return back()->withErrors([
                'fecha_baja' => 'La fecha de baja no puede ser anterior al inicio del periodo laboral actual.',
            ]);
        }

        $diasLaborados = $fechaIngreso ? DiasLaborados::contarSinDomingos($fechaIngreso, $fechaBaja) : 0;
        $diasLaboradosAnioBaja = $fechaIngreso ? DiasLaborados::contarAnioDeBaja($fechaIngreso, $fechaBaja) : 0;
        $this->moverFotoEmpleadoABajas($empleado);

        $datosBaja = [
            'estatus' => false,
            'numero_empleado_baja' => $empleado->numero_empleado_baja ?: $empleado->numero_empleado,
            'numero_empleado' => null,
            'fecha_baja' => $fechaBaja->format('Y-m-d'),
            'dias_laborados' => $diasLaborados,
            'motivo_baja' => $validated['motivo_baja'] ?? null,
        ];

        if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
            $datosBaja['dias_laborados_anio_baja'] = $diasLaboradosAnioBaja;
        }

        $empleado->update($datosBaja);

        FirebaseSyncService::desactivarAccesoApp($empleado);
        FirebaseJobDispatcher::employee($empleado);

        return redirect()->back()->with('success', 'Empleado enviado a papelera y numero liberado.');
    }

    public function actualizarFechaReingreso(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'fecha_reingreso' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'fecha_reingreso.required' => 'Indica la fecha efectiva de reingreso.',
            'fecha_reingreso.before_or_equal' => 'La fecha de reingreso no puede ser futura.',
        ]);

        if (!$empleado->estatus || !$empleado->fecha_reingreso) {
            return back()->withErrors([
                'fecha_reingreso' => 'Este empleado no tiene un reingreso activo que pueda editarse.',
            ]);
        }

        $registro = $empleado->reingresos()
            ->whereDate('fecha_reingreso', $empleado->fecha_reingreso)
            ->latest('id')
            ->first();
        $fechaReingreso = Carbon::parse($validated['fecha_reingreso'])->startOfDay();
        $fechaBajaAnterior = $registro?->fecha_baja_anterior
            ? Carbon::parse($registro->fecha_baja_anterior)->startOfDay()
            : null;

        if ($fechaBajaAnterior && $fechaReingreso->lte($fechaBajaAnterior)) {
            return back()->withErrors([
                'fecha_reingreso' => 'El reingreso debe ser posterior a la baja anterior.',
            ]);
        }

        DB::transaction(function () use ($empleado, $registro, $fechaReingreso) {
            $registro?->update(['fecha_reingreso' => $fechaReingreso->format('Y-m-d')]);
            $empleado->update(['fecha_reingreso' => $fechaReingreso->format('Y-m-d')]);
        });

        FirebaseJobDispatcher::employee($empleado->fresh());

        return back()->with('success', 'Fecha de reingreso actualizada correctamente.');
    }

    public function restaurar(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'fecha_reingreso' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'fecha_reingreso.required' => 'Indica la fecha real del reingreso.',
            'fecha_reingreso.before_or_equal' => 'La fecha de reingreso no puede ser futura.',
        ]);

        if ($empleado->estatus) {
            return back()->withErrors(['fecha_reingreso' => 'El empleado ya se encuentra activo.']);
        }

        $fechaReingreso = Carbon::parse($validated['fecha_reingreso'])->startOfDay();
        $fechaBajaAnterior = $empleado->fecha_baja
            ? Carbon::parse($empleado->fecha_baja)->startOfDay()
            : null;

        if ($fechaBajaAnterior && $fechaReingreso->lte($fechaBajaAnterior)) {
            return back()->withErrors([
                'fecha_reingreso' => 'El reingreso debe ser posterior a la última fecha de baja.',
            ]);
        }

        $numeroAnterior = $empleado->numero_empleado ?: $empleado->numero_empleado_baja;
        $numeroOcupado = $numeroAnterior
            ? $this->numeroEmpleadoActivoOcupado($empleado, $numeroAnterior)
            : false;
        $numeroRestaurado = $numeroAnterior && !$numeroOcupado ? $numeroAnterior : null;
        $datosRestaurar = [
            'estatus' => true,
            'numero_empleado' => $numeroRestaurado,
            'fecha_reingreso' => $fechaReingreso->format('Y-m-d'),
            'fecha_baja' => null,
            'dias_laborados' => 0,
            'ajuste_vacaciones' => 0,
            'motivo_baja' => null,
        ];

        if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
            $datosRestaurar['dias_laborados_anio_baja'] = 0;
        }

        DB::transaction(function () use ($empleado, $datosRestaurar, $fechaReingreso, $fechaBajaAnterior, $request) {
            $empleado->reingresos()->create([
                'fecha_reingreso' => $fechaReingreso->format('Y-m-d'),
                'fecha_baja_anterior' => $fechaBajaAnterior?->format('Y-m-d'),
                'registrado_por' => $request->user()?->id,
            ]);

            $empleado->update($datosRestaurar);
        });

        $empleado->refresh();
        $this->moverFotoEmpleadoAActivos($empleado);

        FirebaseJobDispatcher::employee($empleado);

        $mensaje = $numeroOcupado
            ? "Empleado restaurado sin numero. El numero {$numeroAnterior} ya lo usa otro empleado activo; asignale uno nuevo antes de usarlo en checador."
            : 'Empleado restaurado correctamente.';

        return redirect()->back()->with('success', $mensaje);
    }

    public function actualizarFoto(Request $request, Empleado $empleado)
    {
        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto.required' => 'Selecciona una fotografía.',
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.mimes' => 'La fotografía debe ser JPG, PNG o WEBP.',
            'foto.max' => 'La fotografía no debe superar 5 MB.',
        ]);

        $this->reemplazarFotoEmpleado($empleado, $validated['foto']);

        return back()->with('success', 'Fotografía actualizada correctamente.');
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

    private function reemplazarFotoEmpleado(Empleado $empleado, $foto): void
    {
        $directorioActivo = public_path('img/empleados');
        $directorioDestino = $empleado->estatus
            ? $directorioActivo
            : $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';

        if (!is_dir($directorioDestino) && !mkdir($directorioDestino, 0755, true) && !is_dir($directorioDestino)) {
            throw new RuntimeException('No se pudo preparar la carpeta de fotografías.');
        }

        $extension = strtolower((string) ($foto->extension() ?: $foto->getClientOriginalExtension()));
        $claveDestino = $this->claveFotoActual($empleado);
        $nombreDestino = "{$claveDestino}.{$extension}";
        $destino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreDestino;
        $temporal = $directorioDestino . DIRECTORY_SEPARATOR . '.upload-' . Str::uuid() . ".{$extension}";
        $respaldo = null;

        $foto->move($directorioDestino, basename($temporal));

        if (is_file($destino)) {
            $respaldo = $destino . '.backup-' . Str::uuid();

            if (!@rename($destino, $respaldo)) {
                @unlink($temporal);
                throw new RuntimeException('No se pudo respaldar la fotografía anterior.');
            }
        }

        if (!@rename($temporal, $destino)) {
            if ($respaldo && is_file($respaldo)) {
                @rename($respaldo, $destino);
            }

            @unlink($temporal);
            throw new RuntimeException('No se pudo guardar la fotografía nueva.');
        }

        foreach ($this->rutasFotoEmpleado($empleado) as $rutaAnterior) {
            if ($rutaAnterior !== $destino && is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        if ($respaldo && is_file($respaldo)) {
            @unlink($respaldo);
        }
    }

    private function renombrarFotoAlNumeroActual(Empleado $empleado, $numeroAnterior): void
    {
        $claveNueva = $this->claveFotoActual($empleado);
        $claveAnterior = $this->limpiarClaveFoto($numeroAnterior);

        if ($claveNueva === '' || $claveNueva === $claveAnterior) {
            return;
        }

        $directorioActivo = public_path('img/empleados');
        $directorio = $empleado->estatus
            ? $directorioActivo
            : $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';
        $clavesOrigen = collect([
            $claveAnterior,
            ltrim($claveAnterior, '0') ?: $claveAnterior,
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
        ])->filter()->unique();

        foreach ($clavesOrigen as $claveOrigen) {
            foreach ($this->extensionesFotoEmpleado() as $extension) {
                $origen = $directorio . DIRECTORY_SEPARATOR . "{$claveOrigen}.{$extension}";

                if (!is_file($origen)) {
                    continue;
                }

                $destino = $directorio . DIRECTORY_SEPARATOR . "{$claveNueva}.{$extension}";

                if ($origen !== $destino && !@rename($origen, $destino)) {
                    throw new RuntimeException('El número se actualizó, pero no fue posible renombrar su fotografía.');
                }

                foreach ($this->rutasFotoEmpleado($empleado) as $rutaAnterior) {
                    if ($rutaAnterior !== $destino && is_file($rutaAnterior)) {
                        @unlink($rutaAnterior);
                    }
                }

                return;
            }
        }
    }

    private function claveFotoActual(Empleado $empleado): string
    {
        $numero = $this->limpiarClaveFoto(
            $empleado->estatus
                ? $empleado->numero_empleado
                : ($empleado->numero_empleado_baja ?: $empleado->numero_empleado)
        );

        return $numero !== '' ? $numero : "id-{$empleado->id}";
    }

    private function rutasFotoEmpleado(Empleado $empleado): array
    {
        $directorioActivo = public_path('img/empleados');
        $directorioBajas = $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';
        $clavesId = collect([
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
        ]);
        $rutas = $clavesId->flatMap(fn (string $clave) => collect([$directorioActivo, $directorioBajas])
            ->flatMap(fn (string $directorio) => collect($this->extensionesFotoEmpleado())
                ->map(fn (string $extension) => $directorio . DIRECTORY_SEPARATOR . "{$clave}.{$extension}")));

        $numero = $this->limpiarClaveFoto(
            $empleado->estatus ? $empleado->numero_empleado : $empleado->numero_empleado_baja
        );
        $numeroDisponible = $numero !== '' && !$this->numeroEmpleadoActivoOcupado($empleado, $numero);

        if ($numeroDisponible) {
            $clavesNumero = collect([$numero, ltrim($numero, '0') ?: $numero])->unique();
            $rutas = $rutas->merge($clavesNumero
                ->flatMap(fn (string $clave) => collect([$directorioActivo, $directorioBajas])
                    ->flatMap(fn (string $directorio) => collect($this->extensionesFotoEmpleado())
                        ->map(fn (string $extension) => $directorio . DIRECTORY_SEPARATOR . "{$clave}.{$extension}"))));
        }

        return $rutas
            ->unique()
            ->values()
            ->all();
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
            $numero,
            ltrim($numero, '0') ?: $numero,
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
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

    private function moverFotoEmpleadoAActivos(Empleado $empleado): void
    {
        $directorioActivo = public_path('img/empleados');
        $directorioBajas = $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';

        if (!is_dir($directorioBajas)) {
            return;
        }

        if (!is_dir($directorioActivo)) {
            mkdir($directorioActivo, 0755, true);
        }

        $numeroActual = $this->limpiarClaveFoto($empleado->numero_empleado);
        $numeros = collect([$empleado->numero_empleado, $empleado->numero_empleado_baja])
            ->map(fn ($numero) => $this->limpiarClaveFoto($numero))
            ->filter()
            ->flatMap(fn ($numero) => [$numero, ltrim($numero, '0') ?: $numero]);
        $claves = collect([
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
        ])
            ->merge($numeros)
            ->filter()
            ->unique();
        $claveDestino = $numeroActual ?: "id-{$empleado->id}";

        foreach ($claves as $clave) {
            foreach ($this->extensionesFotoEmpleado() as $extension) {
                $origen = $directorioBajas . DIRECTORY_SEPARATOR . "{$clave}.{$extension}";

                if (!is_file($origen)) {
                    continue;
                }

                $destino = $directorioActivo . DIRECTORY_SEPARATOR . "{$claveDestino}.{$extension}";

                if (is_file($destino)) {
                    if (@copy($origen, $destino)) {
                        @unlink($origen);
                    }

                    continue;
                }

                if (!@rename($origen, $destino) && @copy($origen, $destino)) {
                    @unlink($origen);
                }
            }
        }
    }

    private function numeroEmpleadoActivoOcupado(Empleado $empleado, string $numero): bool
    {
        $numero = trim($numero);
        $sinCeros = ltrim($numero, '0') ?: $numero;
        $variantes = collect([$numero, $sinCeros])->filter()->unique()->values()->all();

        return Empleado::query()
            ->whereKeyNot($empleado->id)
            ->where('estatus', true)
            ->whereNotNull('numero_empleado')
            ->pluck('numero_empleado')
            ->contains(function ($numeroRegistrado) use ($variantes, $sinCeros) {
                $numeroRegistrado = trim((string) $numeroRegistrado);

                return in_array($numeroRegistrado, $variantes, true)
                    || (ltrim($numeroRegistrado, '0') ?: $numeroRegistrado) === $sinCeros;
            });
    }

    private function limpiarClaveFoto($valor): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($valor ?? ''));
    }

    private function extensionesFotoEmpleado(): array
    {
        return ['webp', 'jpg', 'jpeg', 'png'];
    }

    private function directoryPayload(Empleado $employee): array
    {
        $attributes = $employee->getAttributes();
        $start = $employee->inicioPeriodoActual();
        $end = $employee->fecha_baja ? Carbon::parse($employee->fecha_baja)->startOfDay() : now()->startOfDay();
        $years = $start && $end->gte($start) ? (int) floor($start->diffInYears($end)) : 0;
        $vacationTotal = $years < 1
            ? 0
            : ($years <= 5 ? 10 + ($years * 2) : 20 + ((int) ceil(($years - 5) / 5) * 2));
        $vacationTaken = round(max(
            (float) ($employee->vacaciones_capturadas_count ?? 0),
            (float) ($employee->vacaciones_pagadas_sum ?? 0)
        ), 2);

        return array_merge($attributes, [
            'estatus' => (bool) $employee->estatus,
            'es_estudiante' => (bool) ($employee->es_estudiante ?? false),
            'fecha_inicio_periodo_actual' => $start?->format('Y-m-d'),
            'antiguedad_anios' => $years,
            'dias_vacaciones_totales' => $vacationTotal,
            'dias_vacaciones_tomados' => $vacationTaken,
            'dias_vacaciones_restantes' => round(
                $vacationTotal - $vacationTaken + (float) ($employee->ajuste_vacaciones ?? 0),
                2
            ),
            'dias_laborados' => $employee->fecha_baja && $employee->fecha_ingreso
                ? DiasLaborados::contarSinDomingos($employee->fecha_ingreso, $employee->fecha_baja)
                : (int) ($employee->dias_laborados ?? 0),
            'dias_laborados_anio_baja' => $employee->fecha_baja && $employee->fecha_ingreso
                ? DiasLaborados::contarAnioDeBaja($employee->fecha_ingreso, $employee->fecha_baja)
                : (int) ($employee->dias_laborados_anio_baja ?? 0),
        ]);
    }
}
