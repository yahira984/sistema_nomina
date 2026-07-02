<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (!Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
                $table->unsignedInteger('dias_laborados_anio_baja')->default(0)->after('dias_laborados');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            if (Schema::hasColumn('empleados', 'dias_laborados_anio_baja')) {
                $table->dropColumn('dias_laborados_anio_baja');
            }
        });
    }
};
