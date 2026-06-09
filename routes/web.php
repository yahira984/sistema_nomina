<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\NominaController;
use App\Http\Controllers\DashboardController; // <-- Agregado
use App\Http\Controllers\BaseDatosController;
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
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Rutas del perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Sistema / Base de datos
    Route::get('/sistema/base-datos', [BaseDatosController::class, 'index'])->name('base-datos.index');
    Route::get('/sistema/base-datos/exportar', [BaseDatosController::class, 'exportar'])->name('base-datos.exportar');
    Route::post('/sistema/base-datos/importar', [BaseDatosController::class, 'importar'])->name('base-datos.importar');

    // Empleados
    Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
    Route::put('/empleados/{empleado}/restaurar', [EmpleadoController::class, 'restaurar'])->name('empleados.restaurar');
    Route::get('/empleados/{id}/perfil', [EmpleadoController::class, 'show'])->name('empleados.show');
    
    // Asistencias
    Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
    Route::post('/asistencias', [AsistenciaController::class, 'store'])->name('asistencias.store');
    Route::put('/asistencias/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencias.update');
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy'])->name('asistencias.destroy');
    Route::post('/asistencias/importar', [AsistenciaController::class, 'importarReloj'])->name('asistencias.importar');
    Route::post('/asistencias/importar/aprobar', [AsistenciaController::class, 'aprobarImportacion'])->name('asistencias.importar.aprobar');
    Route::delete('/asistencias/importar/revision', [AsistenciaController::class, 'descartarImportacion'])->name('asistencias.importar.descartar');
    
    // Nóminas
    Route::get('/nominas', [NominaController::class, 'index'])->name('nominas.index');
    Route::get('/nominas/generar/{empleado_id}', [NominaController::class, 'generarRecibo'])->name('nominas.generar');
    Route::get('/nominas/excel-individual/{empleado_id}', [NominaController::class, 'exportarExcelIndividual'])->name('nominas.excel-individual');
    Route::get('/nominas/descargar/{nomina}', [NominaController::class, 'descargar'])->name('nominas.descargar');
    Route::put('/nominas/ajustes/{empleado_id}', [NominaController::class, 'actualizarAjustes'])->name('nominas.ajustes');
    Route::put('/nominas/{nomina}/pagar', [NominaController::class, 'pagar'])->name('nominas.pagar');
    Route::get('/nominas/reporte-global/{semana}', [NominaController::class, 'reporteGlobal'])->name('nominas.reporte');
});

require __DIR__.'/auth.php';
