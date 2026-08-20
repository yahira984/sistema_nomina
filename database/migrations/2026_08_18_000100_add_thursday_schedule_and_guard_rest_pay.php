<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_rules') && !Schema::hasColumn('work_rules', 'hora_salida_jueves')) {
            Schema::table('work_rules', function (Blueprint $table) {
                $table->time('hora_salida_jueves')->nullable()->after('hora_salida');
            });
        }

        if (Schema::hasTable('nominas')) {
            $addDiasDescanso = !Schema::hasColumn('nominas', 'dias_descanso_trabajados');
            $addPagoDescanso = !Schema::hasColumn('nominas', 'pago_descanso_trabajado');

            Schema::table('nominas', function (Blueprint $table) use ($addDiasDescanso, $addPagoDescanso) {
                if ($addDiasDescanso) {
                    $table->decimal('dias_descanso_trabajados', 8, 2)->default(0)->after('pago_festivo_trabajado');
                }
                if ($addPagoDescanso) {
                    $table->decimal('pago_descanso_trabajado', 12, 2)->default(0)->after('dias_descanso_trabajados');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_rules') && Schema::hasColumn('work_rules', 'hora_salida_jueves')) {
            Schema::table('work_rules', fn (Blueprint $table) => $table->dropColumn('hora_salida_jueves'));
        }

        if (Schema::hasTable('nominas')) {
            $columns = collect(['pago_descanso_trabajado', 'dias_descanso_trabajados'])
                ->filter(fn (string $column) => Schema::hasColumn('nominas', $column))
                ->all();
            if ($columns !== []) {
                Schema::table('nominas', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
    }
};
