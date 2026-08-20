<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('empleados', 'fecha_limite_servicio')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->date('fecha_limite_servicio')->nullable()->after('fecha_inicio_servicio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empleados', 'fecha_limite_servicio')) {
            Schema::table('empleados', fn (Blueprint $table) => $table->dropColumn('fecha_limite_servicio'));
        }
    }
};
