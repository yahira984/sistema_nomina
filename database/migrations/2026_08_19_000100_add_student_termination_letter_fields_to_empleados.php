<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (! Schema::hasColumn('empleados', 'matricula_estudiante')) {
                $table->string('matricula_estudiante')->nullable();
            }
            if (! Schema::hasColumn('empleados', 'encargado_estadias_escuela')) {
                $table->string('encargado_estadias_escuela')->nullable();
            }
            if (! Schema::hasColumn('empleados', 'fecha_termino_servicio')) {
                $table->date('fecha_termino_servicio')->nullable();
            }
            if (! Schema::hasColumn('empleados', 'evaluacion_estadia')) {
                $table->decimal('evaluacion_estadia', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            foreach (['matricula_estudiante', 'encargado_estadias_escuela', 'fecha_termino_servicio', 'evaluacion_estadia'] as $column) {
                if (Schema::hasColumn('empleados', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
