<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->date('fecha'); 
            
            // --- NUEVOS CAMPOS DE LÓGICA ---
            $table->string('tipo_asistencia')->default('Normal'); // Puede ser: 'Normal', 'Falta', o 'Incapacidad'
            $table->integer('minutos_tarde')->default(0); // Ej: Si llega 8:17, aquí guardamos un 17
            
            $table->time('hora_entrada')->nullable(); 
            $table->time('hora_salida')->nullable();  
            $table->decimal('horas_trabajadas', 5, 2)->default(0);
            $table->decimal('horas_extra', 5, 2)->default(0); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};