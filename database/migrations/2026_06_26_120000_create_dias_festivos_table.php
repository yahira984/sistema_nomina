<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dias_festivos')) {
            return;
        }

        Schema::create('dias_festivos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre');
            $table->string('tipo', 30)->default('oficial');
            $table->boolean('es_oficial')->default(true);
            $table->boolean('activo')->default(true);
            $table->string('origen', 30)->default('sistema');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'activo']);
            $table->index(['es_oficial', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_festivos');
    }
};
