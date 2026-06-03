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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id(); // Este será el número de empleado (ej. 84)
            $table->string('nombre_completo');
            $table->string('puesto')->nullable();
            $table->decimal('sueldo_por_hora', 8, 2); // Para guardar los $27.00
            $table->string('nss')->nullable(); // Número de Seguro Social
            $table->string('rfc')->nullable();
            $table->boolean('estatus')->default(true); // Para saber si sigue trabajando ahí
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
