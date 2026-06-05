<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Ya no intentamos crear forma_pago, solo los campos nuevos de dinero
            $table->decimal('sueldo_semanal', 8, 2)->default(0)->after('forma_pago');
            $table->decimal('descuento_imss', 8, 2)->default(0)->after('cuota_prestamo');
            $table->decimal('descuento_isr', 8, 2)->default(0)->after('descuento_imss');
            $table->decimal('descuento_infonavit', 8, 2)->default(0)->after('descuento_isr');

            // Borramos los viejos que ya no sirven
            $table->dropColumn(['sueldo_por_hora', 'cuota_seguro']);
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['sueldo_semanal', 'descuento_imss', 'descuento_isr', 'descuento_infonavit']);
            $table->decimal('sueldo_por_hora', 8, 2)->default(0);
            $table->decimal('cuota_seguro', 8, 2)->default(0);
        });
    }
};