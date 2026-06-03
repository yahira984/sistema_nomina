<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('nominas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
        $table->integer('numero_semana'); // Ej: Semana 20
        $table->date('fecha_inicio'); // El Miércoles que empezó
        $table->date('fecha_fin'); // El Martes que terminó
        
        $table->decimal('horas_normales', 5, 2)->default(0); 
        $table->decimal('horas_extra', 5, 2)->default(0); // Lo que se trabajó en sábado
        
        $table->decimal('total_percepciones', 8, 2)->default(0); // El dinero ganado
        $table->decimal('total_deducciones', 8, 2)->default(0); // Faltas, retardos, etc.
        $table->decimal('pago_neto', 8, 2)->default(0); // Lo que se le deposita/da en efectivo
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nominas');
    }
};
