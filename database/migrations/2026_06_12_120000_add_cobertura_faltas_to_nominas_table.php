<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'faltas_cubiertas_vacaciones')) {
                $table->decimal('faltas_cubiertas_vacaciones', 6, 2)->default(0)->after('dias_vacaciones_pagadas');
            }

            if (!Schema::hasColumn('nominas', 'faltas_cubiertas_incapacidad')) {
                $table->decimal('faltas_cubiertas_incapacidad', 6, 2)->default(0)->after('faltas_cubiertas_vacaciones');
            }

            if (!Schema::hasColumn('nominas', 'dias_vacaciones_adicionales')) {
                $table->decimal('dias_vacaciones_adicionales', 6, 2)->default(0)->after('faltas_cubiertas_incapacidad');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            foreach ([
                'faltas_cubiertas_vacaciones',
                'faltas_cubiertas_incapacidad',
                'dias_vacaciones_adicionales',
            ] as $columna) {
                if (Schema::hasColumn('nominas', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
