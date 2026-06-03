<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('asistencias', function (Blueprint $table) {
        $table->id();
        // Conectamos con la tabla de empleados. Si se borra un empleado, se borran sus asistencias.
        $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
        $table->date('fecha'); // Ej: 2026-06-03
        $table->time('hora_entrada')->nullable(); // Ej: 08:00:00
        $table->time('hora_salida')->nullable();  // Ej: 17:00:00
        $table->decimal('horas_trabajadas', 5, 2)->default(0); // Ej: 8.50 horas
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
