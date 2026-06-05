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
            $table->string('numero_empleado')->unique()->nullable();
            $table->string('nombre_completo');
            $table->string('puesto')->nullable();
            
            $table->decimal('sueldo_por_hora', 8, 2); 
            
            // --- NUEVOS CAMPOS PARA DEDUCCIONES ---
            $table->decimal('cuota_prestamo', 8, 2)->default(0); // Lo que se le descuenta de préstamo a la semana
            $table->decimal('saldo_prestamo', 8, 2)->default(0);
            $table->decimal('cuota_seguro', 8, 2)->default(0); // Lo que se le descuenta de impuestos/seguro
            
            $table->string('banco')->nullable();
            $table->string('numero_cuenta', 20)->nullable();
            $table->string('nss')->nullable(); 
            $table->string('rfc')->nullable();
            $table->boolean('estatus')->default(true); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};