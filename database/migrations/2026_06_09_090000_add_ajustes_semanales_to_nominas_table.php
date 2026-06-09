<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'prestamo_otorgado')) {
                $table->decimal('prestamo_otorgado', 10, 2)->default(0)->after('horas_extra');
            }

            if (!Schema::hasColumn('nominas', 'prestamo_descuento')) {
                $table->decimal('prestamo_descuento', 10, 2)->default(0)->after('prestamo_otorgado');
            }

            if (!Schema::hasColumn('nominas', 'deduccion_manual')) {
                $table->decimal('deduccion_manual', 10, 2)->default(0)->after('prestamo_descuento');
            }

            if (!Schema::hasColumn('nominas', 'faltas_pagadas')) {
                $table->unsignedTinyInteger('faltas_pagadas')->default(0)->after('deduccion_manual');
            }

            if (!Schema::hasColumn('nominas', 'horas_extra_pagadas')) {
                $table->decimal('horas_extra_pagadas', 6, 2)->default(0)->after('faltas_pagadas');
            }

            if (!Schema::hasColumn('nominas', 'horas_adeudo_generadas')) {
                $table->decimal('horas_adeudo_generadas', 6, 2)->default(0)->after('horas_extra_pagadas');
            }

            if (!Schema::hasColumn('nominas', 'horas_adeudo_descontadas')) {
                $table->decimal('horas_adeudo_descontadas', 6, 2)->default(0)->after('horas_adeudo_generadas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $columnas = [
                'prestamo_otorgado',
                'prestamo_descuento',
                'deduccion_manual',
                'faltas_pagadas',
                'horas_extra_pagadas',
                'horas_adeudo_generadas',
                'horas_adeudo_descontadas',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('nominas', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
