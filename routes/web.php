<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\NominaController;
use App\Models\Empleado;
use App\Models\Nomina;
use Carbon\Carbon;
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

// Ruta del Dashboard CON la lógica de las tarjetas
Route::get('/dashboard', function () {
    // 1. Misma lógica exacta que en Nóminas (Corte en el último Martes)
    $hoy = Carbon::now();
    $martesAutomatico = $hoy->isTuesday() ? $hoy->copy()->endOfDay() : $hoy->copy()->previous(Carbon::TUESDAY)->endOfDay();
    $inicioSemana = $martesAutomatico->copy()->subDays(6)->startOfDay();
    
    // Esta es la semana contable que se está pagando actualmente (Ej. 22)
    $semanaActual = $inicioSemana->weekOfYear;

    // 2. Contamos cuántos trabajadores activos hay
    $empleadosActivos = Empleado::where('estatus', true)->count();

    // 3. Sumamos cuánto dinero se ha pagado en esta semana contable
    $totalPagado = Nomina::where('numero_semana', $semanaActual)->sum('pago_neto');

    return Inertia::render('Dashboard', [
        'empleadosActivos' => $empleadosActivos,
        'semanaActual' => $semanaActual,
        'totalPagado' => $totalPagado
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Rutas del perfil de usuario (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de control de empleados
    Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');

    // Rutas de asistencias
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::post('/asistencias', [AsistenciaController::class, 'store'])->name('asistencias.store');
    Route::put('/asistencias/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencias.update');
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy'])->name('asistencias.destroy');
    Route::post('/asistencias/importar', [AsistenciaController::class, 'importarReloj'])->name('asistencias.importar');
    Route::post('/asistencias/importar/aprobar', [AsistenciaController::class, 'aprobarImportacion'])->name('asistencias.importar.aprobar');
    Route::delete('/asistencias/importar/revision', [AsistenciaController::class, 'descartarImportacion'])->name('asistencias.importar.descartar');

    // Rutas de nominas (Ya limpiecita sin duplicados)
    Route::get('/nominas', [NominaController::class, 'index'])->name('nominas.index');
    Route::get('/nominas/generar/{empleado_id}', [NominaController::class, 'generarRecibo'])->name('nominas.generar');
    Route::get('/nominas/excel-individual/{empleado_id}', [NominaController::class, 'exportarExcelIndividual'])->name('nominas.excel-individual');
    Route::get('/nominas/descargar/{nomina}', [NominaController::class, 'descargar'])->name('nominas.descargar');
    Route::put('/nominas/{nomina}/pagar', [NominaController::class, 'pagar'])->name('nominas.pagar');
    Route::get('/nominas/reporte-global/{semana}', [NominaController::class, 'reporteGlobal'])->name('nominas.reporte');
});

require __DIR__.'/auth.php';
