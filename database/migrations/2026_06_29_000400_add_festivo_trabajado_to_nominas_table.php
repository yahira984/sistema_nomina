<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'dias_festivos_trabajados')) {
                $table->decimal('dias_festivos_trabajados', 6, 2)->default(0)->after('dias_vacaciones_adicionales');
            }

            if (!Schema::hasColumn('nominas', 'horas_festivas_trabajadas')) {
                $table->decimal('horas_festivas_trabajadas', 6, 2)->default(0)->after('dias_festivos_trabajados');
            }

            if (!Schema::hasColumn('nominas', 'pago_festivo_trabajado')) {
                $table->decimal('pago_festivo_trabajado', 10, 2)->default(0)->after('horas_festivas_trabajadas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            foreach (['pago_festivo_trabajado', 'horas_festivas_trabajadas', 'dias_festivos_trabajados'] as $columna) {
                if (Schema::hasColumn('nominas', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
