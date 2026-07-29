<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asistencias')) {
            $duplicates = DB::table('asistencias')
                ->select('empleado_id', 'fecha', DB::raw('COUNT(*) as total'))
                ->groupBy('empleado_id', 'fecha')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (!$duplicates) {
                $this->addIndex('asistencias', ['empleado_id', 'fecha'], 'asist_employee_date_unique', true);
            }

            $this->addIndex('asistencias', ['fecha'], 'asist_date_idx');
            $this->addIndex('asistencias', ['tipo_asistencia', 'fecha'], 'asist_type_date_idx');
            $this->addIndex('asistencias', ['empleado_id', 'tipo_asistencia', 'fecha'], 'asist_employee_type_date_idx');
        }

        if (Schema::hasTable('nominas')) {
            $duplicates = DB::table('nominas')
                ->select('empleado_id', 'fecha_inicio', 'fecha_fin', DB::raw('COUNT(*) as total'))
                ->groupBy('empleado_id', 'fecha_inicio', 'fecha_fin')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (!$duplicates) {
                $this->addIndex('nominas', ['empleado_id', 'fecha_inicio', 'fecha_fin'], 'payroll_employee_period_unique', true);
            }

            $this->addIndex('nominas', ['fecha_fin', 'pagado'], 'payroll_end_paid_idx');
            $this->addIndex('nominas', ['numero_semana', 'fecha_fin'], 'payroll_week_year_idx');
            $this->addIndex('nominas', ['empleado_id', 'pagado', 'fecha_fin'], 'payroll_employee_paid_end_idx');
        }

        if (Schema::hasTable('empleados')) {
            $this->addIndex('empleados', ['nombre_completo'], 'employee_name_idx');
            $this->addIndex('empleados', ['puesto', 'estatus'], 'employee_position_status_idx');
            $this->addIndex('empleados', ['banco', 'estatus'], 'employee_bank_status_idx');
            $this->addIndex('empleados', ['fecha_ingreso', 'fecha_baja'], 'employee_dates_idx');
        }

        if (Schema::hasTable('audit_logs')) {
            $this->addIndex('audit_logs', ['auditable_type', 'auditable_id', 'created_at'], 'audit_subject_created_idx');
            $this->addIndex('audit_logs', ['user_id', 'created_at'], 'audit_user_created_idx');
            $this->addIndex('audit_logs', ['event', 'created_at'], 'audit_event_created_idx');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            $this->dropIndex('audit_logs', 'audit_event_created_idx');
            $this->dropIndex('audit_logs', 'audit_user_created_idx');
            $this->dropIndex('audit_logs', 'audit_subject_created_idx');
        }

        if (Schema::hasTable('empleados')) {
            $this->dropIndex('empleados', 'employee_dates_idx');
            $this->dropIndex('empleados', 'employee_bank_status_idx');
            $this->dropIndex('empleados', 'employee_position_status_idx');
            $this->dropIndex('empleados', 'employee_name_idx');
        }

        if (Schema::hasTable('nominas')) {
            $this->dropIndex('nominas', 'payroll_employee_paid_end_idx');
            $this->dropIndex('nominas', 'payroll_week_year_idx');
            $this->dropIndex('nominas', 'payroll_end_paid_idx');
            $this->dropIndex('nominas', 'payroll_employee_period_unique');
        }

        if (Schema::hasTable('asistencias')) {
            $this->dropIndex('asistencias', 'asist_employee_type_date_idx');
            $this->dropIndex('asistencias', 'asist_type_date_idx');
            $this->dropIndex('asistencias', 'asist_date_idx');
            $this->dropIndex('asistencias', 'asist_employee_date_unique');
        }
    }

    private function addIndex(string $tableName, array $columns, string $name, bool $unique = false): void
    {
        if ($this->indexExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $name, $unique) {
            $unique ? $table->unique($columns, $name) : $table->index($columns, $name);
        });
    }

    private function dropIndex(string $tableName, string $name): void
    {
        if (!$this->indexExists($tableName, $name)) {
            return;
        }

        Schema::table($tableName, fn (Blueprint $table) => $table->dropIndex($name));
    }

    private function indexExists(string $tableName, string $name): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }
};
