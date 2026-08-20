<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'universidad')) $table->string('universidad')->nullable();
            if (!Schema::hasColumn('empleados', 'carrera')) $table->string('carrera')->nullable();
            if (!Schema::hasColumn('empleados', 'horas_servicio_requeridas')) $table->decimal('horas_servicio_requeridas', 8, 2)->nullable();
            if (!Schema::hasColumn('empleados', 'fecha_inicio_servicio')) $table->date('fecha_inicio_servicio')->nullable();
            if (!Schema::hasColumn('empleados', 'area_proyecto_servicio')) $table->string('area_proyecto_servicio')->nullable();
            if (!Schema::hasColumn('empleados', 'observaciones_servicio')) $table->text('observaciones_servicio')->nullable();
            if (!Schema::hasColumn('empleados', 'servicio_pausado')) $table->boolean('servicio_pausado')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            foreach (['universidad', 'carrera', 'horas_servicio_requeridas', 'fecha_inicio_servicio', 'area_proyecto_servicio', 'observaciones_servicio', 'servicio_pausado'] as $column) {
                if (Schema::hasColumn('empleados', $column)) $table->dropColumn($column);
            }
        });
    }
};
