<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->index(['empleado_id', 'fecha'], 'asist_emp_fecha_idx');
            $table->index(['fecha', 'tipo_asistencia'], 'asist_fecha_tipo_idx');
        });

        Schema::table('nominas', function (Blueprint $table) {
            $table->index(['empleado_id', 'fecha_inicio', 'fecha_fin'], 'nom_emp_periodo_idx');
            $table->index(['fecha_inicio', 'fecha_fin'], 'nom_periodo_idx');
            $table->index(['pagado', 'empleado_id'], 'nom_pagado_emp_idx');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->index(['estatus', 'numero_empleado'], 'emp_estatus_num_idx');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropIndex('emp_estatus_num_idx');
        });

        Schema::table('nominas', function (Blueprint $table) {
            $table->dropIndex('nom_pagado_emp_idx');
            $table->dropIndex('nom_periodo_idx');
            $table->dropIndex('nom_emp_periodo_idx');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex('asist_fecha_tipo_idx');
            $table->dropIndex('asist_emp_fecha_idx');
        });
    }
};
