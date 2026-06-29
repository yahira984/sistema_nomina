<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'deposito_imss')) {
                $table->decimal('deposito_imss', 10, 2)->default(0)->after('pago_neto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (Schema::hasColumn('nominas', 'deposito_imss')) {
                $table->dropColumn('deposito_imss');
            }
        });
    }
};
