<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_otorgado')) {
                $table->decimal('prestamo_saldo_aplicado_otorgado', 10, 2)->nullable()->after('prestamo_descuento');
            }

            if (!Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_descuento')) {
                $table->decimal('prestamo_saldo_aplicado_descuento', 10, 2)->nullable()->after('prestamo_saldo_aplicado_otorgado');
            }

            if (!Schema::hasColumn('nominas', 'prestamo_saldo_aplicado_at')) {
                $table->timestamp('prestamo_saldo_aplicado_at')->nullable()->after('prestamo_saldo_aplicado_descuento');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $columnas = [
                'prestamo_saldo_aplicado_otorgado',
                'prestamo_saldo_aplicado_descuento',
                'prestamo_saldo_aplicado_at',
            ];

            foreach ($columnas as $columna) {
                if (Schema::hasColumn('nominas', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
