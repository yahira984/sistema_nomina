<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\DashboardController; // <-- Agregado
use App\Http\Controllers\BaseDatosController;
use App\Http\Controllers\DiaFestivoController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\UsuarioSeguridadController;
use App\Models\Empleado;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 🔥 RUTA DEL DASHBOARD LIMPIA (Apunta al controlador)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'approved', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'approved', 'audit'])->group(function () {
    // Rutas del perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Seguridad
    Route::get('/seguridad/usuarios', [UsuarioSeguridadController::class, 'index'])
        ->middleware('permission:sistema.users')
        ->name('seguridad.usuarios.index');
    Route::put('/seguridad/usuarios/{user}', [UsuarioSeguridadController::class, 'update'])
        ->middleware('permission:sistema.users')
        ->name('seguridad.usuarios.update');
    Route::delete('/seguridad/usuarios/{user}', [UsuarioSeguridadController::class, 'destroy'])
        ->middleware('permission:sistema.users')
        ->name('seguridad.usuarios.destroy');
    Route::get('/seguridad/auditoria', [AuditoriaController::class, 'index'])
        ->middleware('permission:sistema.audit')
        ->name('seguridad.auditoria.index');
    Route::delete('/seguridad/auditoria', [AuditoriaController::class, 'purge'])
        ->middleware('permission:sistema.audit')
        ->name('seguridad.auditoria.purge');
    Route::delete('/seguridad/auditoria/{auditLog}', [AuditoriaController::class, 'destroy'])
        ->middleware('permission:sistema.audit')
        ->name('seguridad.auditoria.destroy');

    // Sistema / Base de datos
    Route::get('/sistema/base-datos', [BaseDatosController::class, 'index'])->middleware('permission:sistema.backups')->name('base-datos.index');
    Route::get('/sistema/base-datos/exportar', [BaseDatosController::class, 'exportar'])->middleware('permission:sistema.backups')->name('base-datos.exportar');
    Route::post('/sistema/base-datos/importar', [BaseDatosController::class, 'importar'])->middleware('permission:sistema.backups')->name('base-datos.importar');
    Route::get('/sistema/dias-festivos', [DiaFestivoController::class, 'index'])->middleware('permission:sistema.dias_festivos')->name('dias-festivos.index');
    Route::post('/sistema/dias-festivos', [DiaFestivoController::class, 'store'])->middleware('permission:sistema.dias_festivos')->name('dias-festivos.store');
    Route::put('/sistema/dias-festivos/{diaFestivo}', [DiaFestivoController::class, 'update'])->middleware('permission:sistema.dias_festivos')->name('dias-festivos.update');
    Route::delete('/sistema/dias-festivos/{diaFestivo}', [DiaFestivoController::class, 'destroy'])->middleware('permission:sistema.dias_festivos')->name('dias-festivos.destroy');
    Route::post('/sistema/dias-festivos/generar', [DiaFestivoController::class, 'generar'])->middleware('permission:sistema.dias_festivos')->name('dias-festivos.generar');

    // Empleados
    Route::get('/empleados', [EmpleadoController::class, 'index'])->middleware('permission:empleados.view')->name('empleados.index');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->middleware('permission:empleados.manage')->name('empleados.store');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->middleware('permission:empleados.manage')->name('empleados.update');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->middleware('permission:empleados.manage')->name('empleados.destroy');
    Route::put('/empleados/{empleado}/restaurar', [EmpleadoController::class, 'restaurar'])->middleware('permission:empleados.manage')->name('empleados.restaurar');
    Route::post('/empleados/{empleado}/acceso-app', [EmpleadoController::class, 'guardarAccesoApp'])->middleware('permission:empleados.manage')->name('empleados.acceso-app.guardar');
    Route::delete('/empleados/{empleado}/acceso-app', [EmpleadoController::class, 'desactivarAccesoApp'])->middleware('permission:empleados.manage')->name('empleados.acceso-app.desactivar');
    Route::patch('/empleados/{empleado}/fecha-baja', [EmpleadoController::class, 'actualizarFechaBaja'])->name('empleados.fecha-baja.actualizar');
    Route::get('/empleados/fotos/{empleado}', function (Empleado $empleado) {
        $limpiarClave = fn ($valor) => preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($valor ?? ''));
        $clavesId = collect([
            "id-{$empleado->id}",
            "empleado-{$empleado->id}",
            (string) $empleado->id,
        ])
            ->map($limpiarClave)
            ->filter()
            ->unique()
            ->values();

        $numerosEmpleado = collect([$empleado->numero_empleado, $empleado->numero_empleado_baja])
            ->map($limpiarClave)
            ->filter()
            ->flatMap(fn ($clave) => [$clave, ltrim($clave, '0') ?: $clave])
            ->unique()
            ->values();

        $numeroUsadoPorActivo = false;
        foreach ($numerosEmpleado as $numero) {
            $variantes = collect([$numero, ltrim($numero, '0') ?: $numero])->unique()->values()->all();
            if (Empleado::query()
                ->whereKeyNot($empleado->id)
                ->where('estatus', true)
                ->whereIn('numero_empleado', $variantes)
                ->exists()) {
                $numeroUsadoPorActivo = true;
                break;
            }
        }

        $directorioActivo = public_path('img/empleados');
        $directorioBajas = $directorioActivo . DIRECTORY_SEPARATOR . 'bajas';
        $busquedas = [];

        if (!$empleado->estatus) {
            $busquedas[] = [$directorioBajas, $clavesId->merge($numerosEmpleado)->unique()->values()];
        }

        $busquedas[] = [$directorioActivo, $clavesId];

        if ($empleado->estatus || !$numeroUsadoPorActivo) {
            $busquedas[] = [$directorioActivo, $numerosEmpleado];
        }

        if ($empleado->estatus) {
            $busquedas[] = [$directorioBajas, $clavesId];
        }

        foreach ($busquedas as [$directorio, $claves]) {
            foreach ($claves as $clave) {
                foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
                    $path = $directorio . DIRECTORY_SEPARATOR . "{$clave}.{$extension}";

                    if (is_file($path)) {
                        return response()->file($path, [
                            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                            'Pragma' => 'no-cache',
                            'Expires' => '0',
                        ]);
                    }
                }
            }
        }

        return response('', 404)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    })->whereNumber('empleado')->middleware('permission:empleados.view')->name('empleados.foto');
    Route::get('/empleados/{id}/perfil', [EmpleadoController::class, 'show'])->middleware('permission:empleados.view')->name('empleados.show');
    
    // Asistencias
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->middleware('permission:asistencias.view')->name('asistencias.index');
    Route::post('/asistencias', [AsistenciaController::class, 'store'])->middleware('permission:asistencias.manage')->name('asistencias.store');
    Route::put('/asistencias/{asistencia}', [AsistenciaController::class, 'update'])->middleware('permission:asistencias.manage')->name('asistencias.update');
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy'])->middleware('permission:asistencias.manage')->name('asistencias.destroy');
    Route::get('/asistencias/exportar-semana', [AsistenciaController::class, 'exportarSemana'])->middleware('permission:asistencias.export')->name('asistencias.exportar-semana');
    Route::get('/asistencias/horas-alumnos', [AsistenciaController::class, 'horasAlumnos'])->middleware('permission:asistencias.view')->name('asistencias.alumnos-horas');
    Route::get('/asistencias/horas-alumnos/pdf', [AsistenciaController::class, 'imprimirHorasAlumnos'])->middleware('permission:asistencias.export')->name('asistencias.alumnos-horas.pdf');
    Route::post('/asistencias/importar', [AsistenciaController::class, 'importarReloj'])->middleware('permission:asistencias.import')->name('asistencias.importar');
    Route::post('/asistencias/importar/aprobar', [AsistenciaController::class, 'aprobarImportacion'])->middleware('permission:asistencias.import')->name('asistencias.importar.aprobar');
    Route::delete('/asistencias/importar/revision', [AsistenciaController::class, 'descartarImportacion'])->middleware('permission:asistencias.import')->name('asistencias.importar.descartar');
    
    // Nóminas
    Route::get('/nominas', [NominaController::class, 'index'])->middleware('permission:nominas.view')->name('nominas.index');
    Route::get('/nominas/generar/{empleado_id}', [NominaController::class, 'generarRecibo'])->middleware('permission:nominas.manage')->name('nominas.generar');
    Route::get('/nominas/excel-individual/{empleado_id}', [NominaController::class, 'exportarExcelIndividual'])->middleware('permission:nominas.export')->name('nominas.excel-individual');
    Route::get('/nominas/recibos-masivos', [NominaController::class, 'generarRecibosMasivos'])->middleware('permission:nominas.export')->name('nominas.recibos-masivos');
    Route::get('/nominas/diferencia-imss/{semana}', [NominaController::class, 'reporteDiferenciaImss'])->middleware('permission:nominas.export')->name('nominas.diferencia-imss');
    Route::get('/nominas/diferencia-imss/{semana}/recibos', [NominaController::class, 'recibosDiferenciaImss'])->middleware('permission:nominas.export')->name('nominas.diferencia-imss.recibos');
    Route::get('/nominas/descargar/{nomina}', [NominaController::class, 'descargar'])->middleware('permission:nominas.export')->name('nominas.descargar');
    Route::put('/nominas/ajustes/{empleado_id}', [NominaController::class, 'actualizarAjustes'])->middleware('permission:nominas.manage')->name('nominas.ajustes');
    Route::put('/nominas/diferencia-imss/{empleado_id}', [NominaController::class, 'actualizarDiferenciaImss'])->middleware('permission:nominas.manage')->name('nominas.diferencia-imss.update');
    Route::put('/nominas/pagos-masivos', [NominaController::class, 'actualizarPagosMasivos'])->middleware('permission:nominas.pay')->name('nominas.pagos-masivos');
    Route::put('/nominas/{nomina}/pagar', [NominaController::class, 'pagar'])->middleware('permission:nominas.pay')->name('nominas.pagar');
    Route::get('/nominas/reporte-global/{semana}', [NominaController::class, 'reporteGlobal'])->middleware('permission:nominas.export')->name('nominas.reporte');
});

require __DIR__.'/auth.php';
