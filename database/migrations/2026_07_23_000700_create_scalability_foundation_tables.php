<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_operations')) {
            Schema::create('system_operations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 80)->index();
                $table->string('status', 30)->default('queued')->index();
                $table->unsignedTinyInteger('progress')->default(0);
                $table->string('message')->nullable();
                $table->string('result_path', 1024)->nullable();
                $table->string('download_name')->nullable();
                $table->string('idempotency_key', 120)->nullable();
                $table->json('payload')->nullable();
                $table->json('result')->nullable();
                $table->longText('error')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'type', 'idempotency_key'], 'sys_ops_user_type_idem_unique');
                $table->index(['user_id', 'created_at'], 'sys_ops_user_created_idx');
                $table->index(['status', 'updated_at'], 'sys_ops_status_updated_idx');
            });
        }

        if (!Schema::hasTable('work_rules')) {
            Schema::create('work_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('scope_type', 30)->default('position');
                $table->string('scope_value')->nullable();
                $table->foreignId('empleado_id')->nullable()->constrained('empleados')->cascadeOnUpdate()->cascadeOnDelete();
                $table->boolean('turno_24x24')->nullable();
                $table->boolean('sin_horas_extra')->nullable();
                $table->boolean('sin_retardos')->nullable();
                $table->boolean('pago_por_hora_topado')->nullable();
                $table->decimal('tope_horas_semanales', 6, 2)->nullable();
                $table->time('hora_entrada')->nullable();
                $table->time('hora_salida')->nullable();
                $table->json('dias_laborales')->nullable();
                $table->date('fecha_referencia_turno')->nullable();
                $table->unsignedSmallInteger('priority')->default(100);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['active', 'scope_type', 'scope_value'], 'work_rules_scope_idx');
                $table->index(['empleado_id', 'active'], 'work_rules_employee_idx');
            });
        }

        if (!Schema::hasTable('labor_calendar_days')) {
            Schema::create('labor_calendar_days', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('kind', 30)->default('non_working');
                $table->string('scope_type', 30)->default('global');
                $table->string('scope_key')->default('global');
                $table->foreignId('empleado_id')->nullable()->constrained('empleados')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('position')->nullable();
                $table->string('shift')->nullable();
                $table->string('name');
                $table->text('notes')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['date', 'scope_type', 'scope_key'], 'labor_calendar_scope_unique');
                $table->index(['date', 'active', 'kind'], 'labor_calendar_date_idx');
                $table->index(['empleado_id', 'date'], 'labor_calendar_employee_idx');
            });
        }

        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedTinyInteger('week_number');
                $table->string('status', 20)->default('open');
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['start_date', 'end_date'], 'payroll_period_dates_unique');
                $table->index(['status', 'end_date'], 'payroll_period_status_idx');
            });
        }

        if (!Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('theme', 20)->default('system');
                $table->string('density', 20)->default('comfortable');
                $table->boolean('sidebar_collapsed')->default(false);
                $table->json('saved_filters')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('system_backups')) {
            Schema::create('system_backups', function (Blueprint $table) {
                $table->id();
                $table->string('disk', 40)->default('local');
                $table->string('path', 1024);
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('checksum', 64)->nullable();
                $table->string('status', 30)->default('created');
                $table->timestamp('verified_at')->nullable();
                $table->text('verification_message')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at'], 'system_backups_status_idx');
            });
        }

        if (!Schema::hasTable('integration_failures')) {
            Schema::create('integration_failures', function (Blueprint $table) {
                $table->id();
                $table->string('integration', 50)->index();
                $table->string('operation', 80)->index();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('payload')->nullable();
                $table->longText('error')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['reference_type', 'reference_id'], 'integration_failure_reference_idx');
                $table->index(['integration', 'status', 'created_at'], 'integration_failure_health_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_failures');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('labor_calendar_days');
        Schema::dropIfExists('work_rules');
        Schema::dropIfExists('system_operations');
    }
};
