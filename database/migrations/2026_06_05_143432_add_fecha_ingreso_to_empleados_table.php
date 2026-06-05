<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->date('fecha_ingreso')->nullable()->after('puesto');
            // Como agregamos estudiantes, el sueldo semanal ya no es obligatorio, puede ser 0
            $table->decimal('sueldo_por_hora', 8, 2)->default(0)->after('sueldo_semanal');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['fecha_ingreso', 'sueldo_por_hora']);
        });
    }
};