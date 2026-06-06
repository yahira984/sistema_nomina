<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Un número entero que puede ser negativo, por defecto 0
            $table->integer('ajuste_vacaciones')->default(0)->after('fecha_ingreso');
        });
    }
    
    public function down()
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('ajuste_vacaciones');
        });
    }
};
