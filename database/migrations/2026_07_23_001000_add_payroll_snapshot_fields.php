<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('nominas')) {
            return;
        }

        Schema::table('nominas', function (Blueprint $table) {
            if (!Schema::hasColumn('nominas', 'calculation_snapshot')) {
                $table->json('calculation_snapshot')->nullable()->after('pago_neto');
            }

            if (!Schema::hasColumn('nominas', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->after('calculation_snapshot')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('nominas', 'calculation_version')) {
                $table->unsignedSmallInteger('calculation_version')->default(1)->after('generated_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('nominas')) {
            return;
        }

        Schema::table('nominas', function (Blueprint $table) {
            if (Schema::hasColumn('nominas', 'generated_by')) {
                $table->dropConstrainedForeignId('generated_by');
            }

            foreach (['calculation_snapshot', 'calculation_version'] as $column) {
                if (Schema::hasColumn('nominas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
