<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'dias_vacaciones_pagadas')) {
                $table->decimal('dias_vacaciones_pagadas', 6, 2)->nullable()->after('faltas_pagadas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            if (Schema::hasColumn('nominas', 'dias_vacaciones_pagadas')) {
                $table->dropColumn('dias_vacaciones_pagadas');
            }
        });
    }
};
