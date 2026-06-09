<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'numero_empleado_baja')) {
                $table->string('numero_empleado_baja')->nullable()->after('numero_empleado');
            }

            if (!Schema::hasColumn('empleados', 'fecha_baja')) {
                $table->date('fecha_baja')->nullable()->after('fecha_ingreso');
            }

            if (!Schema::hasColumn('empleados', 'dias_laborados')) {
                $table->unsignedInteger('dias_laborados')->default(0)->after('fecha_baja');
            }

            if (!Schema::hasColumn('empleados', 'motivo_baja')) {
                $table->string('motivo_baja')->nullable()->after('dias_laborados');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            foreach (['numero_empleado_baja', 'fecha_baja', 'dias_laborados', 'motivo_baja'] as $columna) {
                if (Schema::hasColumn('empleados', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
