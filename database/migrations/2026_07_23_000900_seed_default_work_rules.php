<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('work_rules')) {
            return;
        }

        $now = now();
        $rules = [
            ['name' => 'Vigilancia 24x24 por puesto', 'scope_type' => 'position_contains', 'scope_value' => 'VIGILANCIA', 'turno_24x24' => true, 'sin_horas_extra' => true, 'sin_retardos' => true, 'priority' => 300],
            ['name' => 'Seguridad 24x24 por puesto', 'scope_type' => 'position_contains', 'scope_value' => 'SEGURIDAD', 'turno_24x24' => true, 'sin_horas_extra' => true, 'sin_retardos' => true, 'priority' => 300],
            ['name' => 'Respaldo vigilancia empleado 20', 'scope_type' => 'employee_number', 'scope_value' => '20', 'turno_24x24' => true, 'sin_horas_extra' => true, 'sin_retardos' => true, 'priority' => 250],
            ['name' => 'Respaldo vigilancia empleado 29', 'scope_type' => 'employee_number', 'scope_value' => '29', 'turno_24x24' => true, 'sin_horas_extra' => true, 'sin_retardos' => true, 'priority' => 250],
            ['name' => 'Sin horas extra empleado 8', 'scope_type' => 'employee_number', 'scope_value' => '8', 'sin_horas_extra' => true, 'priority' => 200],
            ['name' => 'Sin horas extra empleado 9', 'scope_type' => 'employee_number', 'scope_value' => '9', 'sin_horas_extra' => true, 'priority' => 200],
            ['name' => 'Sin horas extra empleado 22', 'scope_type' => 'employee_number', 'scope_value' => '22', 'sin_horas_extra' => true, 'priority' => 200],
            ['name' => 'Sin retardos empleado 14', 'scope_type' => 'employee_number', 'scope_value' => '14', 'sin_retardos' => true, 'priority' => 200],
            ['name' => 'Pago topado empleado 76', 'scope_type' => 'employee_number', 'scope_value' => '76', 'sin_retardos' => true, 'pago_por_hora_topado' => true, 'tope_horas_semanales' => 48, 'priority' => 200],
            ['name' => 'Pago topado empleado 78', 'scope_type' => 'employee_number', 'scope_value' => '78', 'sin_retardos' => true, 'pago_por_hora_topado' => true, 'tope_horas_semanales' => 48, 'priority' => 200],
        ];

        foreach ($rules as $rule) {
            DB::table('work_rules')->updateOrInsert(
                [
                    'scope_type' => $rule['scope_type'],
                    'scope_value' => $rule['scope_value'],
                    'name' => $rule['name'],
                ],
                array_merge([
                    'empleado_id' => null,
                    'turno_24x24' => null,
                    'sin_horas_extra' => null,
                    'sin_retardos' => null,
                    'pago_por_hora_topado' => null,
                    'tope_horas_semanales' => null,
                    'hora_entrada' => null,
                    'hora_salida' => null,
                    'dias_laborales' => null,
                    'fecha_referencia_turno' => null,
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rule)
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('work_rules')) {
            DB::table('work_rules')->whereIn('name', [
                'Vigilancia 24x24 por puesto',
                'Seguridad 24x24 por puesto',
                'Respaldo vigilancia empleado 20',
                'Respaldo vigilancia empleado 29',
                'Sin horas extra empleado 8',
                'Sin horas extra empleado 9',
                'Sin horas extra empleado 22',
                'Sin retardos empleado 14',
                'Pago topado empleado 76',
                'Pago topado empleado 78',
            ])->delete();
        }
    }
};
