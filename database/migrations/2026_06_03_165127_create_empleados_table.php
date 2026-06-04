<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id(); 
            $table->string('numero_empleado')->unique()->nullable(); // <-- Aquí está el que faltaba
            $table->string('nombre_completo');
            $table->string('puesto')->nullable();
            $table->decimal('sueldo_por_hora', 8, 2); // Para guardar los $27.00
            $table->string('banco')->nullable();
            $table->string('numero_cuenta', 20)->nullable();
            $table->string('nss')->nullable(); // Número de Seguro Social
            $table->string('rfc')->nullable();
            $table->boolean('estatus')->default(true); // Para saber si sigue trabajando ahí
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};