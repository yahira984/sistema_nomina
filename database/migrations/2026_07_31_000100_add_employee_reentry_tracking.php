<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->date('fecha_reingreso')->nullable()->after('fecha_ingreso')->index();
        });

        Schema::create('empleado_reingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha_reingreso');
            $table->date('fecha_baja_anterior')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empleado_id', 'fecha_reingreso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_reingresos');

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropIndex(['fecha_reingreso']);
            $table->dropColumn('fecha_reingreso');
        });
    }
};
