<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\LaborCalendarDay;
use App\Models\SystemOperation;
use App\Models\User;
use App\Models\WorkRule;
use App\Jobs\GenerateMassPdfJob;
use App\Jobs\PrepareAttendanceImportJob;
use App\Jobs\QueueHeartbeatJob;
use App\Jobs\SyncFirebaseJob;
use App\Services\FirebaseJobDispatcher;
use App\Services\PayrollPeriodService;
use App\Services\PayrollPreflightService;
use App\Services\SystemOperationService;
use App\Support\HorarioLaboralEmpleado;
use App\Support\HorasExtraEmpleado;
use App\Support\SemanaNomina;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScalabilityControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_rules_can_be_configured_without_changing_code(): void
    {
        $employee = $this->employee(['numero_empleado' => '501', 'puesto' => 'TALLER']);

        $this->assertSame(1.5, HorasExtraEmpleado::calcular($employee, '2026-07-20', '08:00', '19:00'));

        WorkRule::create([
            'name' => 'Taller sin horas extra',
            'scope_type' => 'position',
            'scope_value' => 'TALLER',
            'sin_horas_extra' => true,
            'priority' => 500,
            'active' => true,
        ]);

        $this->assertSame(0.0, HorasExtraEmpleado::calcular($employee, '2026-07-20', '08:00', '19:00'));
    }

    public function test_higher_priority_rule_inherits_fields_it_does_not_define(): void
    {
        $employee = $this->employee(['numero_empleado' => '505', 'puesto' => 'TALLER']);
        WorkRule::create([
            'name' => 'Taller sin extras',
            'scope_type' => 'position',
            'scope_value' => 'TALLER',
            'sin_horas_extra' => true,
            'priority' => 100,
            'active' => true,
        ]);
        WorkRule::create([
            'name' => 'Empleado puntualidad especial',
            'scope_type' => 'employee',
            'empleado_id' => $employee->id,
            'sin_retardos' => true,
            'sin_horas_extra' => null,
            'priority' => 500,
            'active' => true,
        ]);

        $resolved = \App\Support\ReglasNominaEmpleado::configuracion($employee);

        $this->assertTrue($resolved['sin_retardos']);
        $this->assertTrue($resolved['sin_horas_extra']);
    }

    public function test_labor_calendar_can_override_a_workday_in_any_year(): void
    {
        $employee = $this->employee(['numero_empleado' => '502']);

        LaborCalendarDay::create([
            'date' => '2029-03-12',
            'kind' => 'non_working',
            'scope_type' => 'global',
            'scope_key' => 'global',
            'name' => 'Cierre extraordinario',
            'active' => true,
        ]);

        $this->assertFalse(HorarioLaboralEmpleado::esDiaLaboral($employee, '2029-03-12'));
        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($employee, '2029-03-13'));
    }

    public function test_locked_week_rejects_payroll_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee(['numero_empleado' => '503']);
        [$start, $end] = SemanaNomina::desdeCorte('2027-05-05');
        $periods = app(PayrollPeriodService::class);
        $periods->setLocked($periods->findOrCreate($start, $end), true, $admin);

        $this->actingAs($admin)
            ->put(route('nominas.ajustes', $employee), [
                'fecha_corte' => '2027-05-05',
                'prestamo_descuento' => 0,
            ])
            ->assertSessionHasErrors('periodo');

        $this->assertDatabaseMissing('nominas', [
            'empleado_id' => $employee->id,
            'fecha_inicio' => $start->format('Y-m-d'),
            'fecha_fin' => $end->format('Y-m-d'),
        ]);
    }

    public function test_idempotency_key_prevents_duplicate_background_operations(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $operations = app(SystemOperationService::class);

        $first = $operations->create('mass_export', $user, ['week' => 30], 'same-request');
        $second = $operations->create('mass_export', $user, ['week' => 30], 'same-request');

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('system_operations', 1);
    }

    public function test_database_rejects_duplicate_attendance_for_employee_and_date(): void
    {
        $employee = $this->employee(['numero_empleado' => '504']);
        $payload = [
            'empleado_id' => $employee->id,
            'fecha' => '2028-01-10',
            'tipo_asistencia' => 'Falta',
            'minutos_tarde' => 0,
            'horas_trabajadas' => 0,
            'horas_extra' => 0,
        ];
        Asistencia::create($payload);

        $this->expectException(QueryException::class);
        Asistencia::create($payload);
    }

    public function test_queue_heartbeat_records_that_a_worker_processed_jobs(): void
    {
        Cache::forget('system:queue-heartbeat');

        (new QueueHeartbeatJob())->handle();

        $this->assertNotNull(Cache::get('system:queue-heartbeat'));
    }

    public function test_firebase_sync_does_not_depend_on_the_database_worker(): void
    {
        Queue::fake();
        config()->set('services.firebase.queue_connection', 'deferred');
        $employee = $this->employee(['numero_empleado' => '506']);

        FirebaseJobDispatcher::employee($employee);

        Queue::assertPushed(SyncFirebaseJob::class, function (SyncFirebaseJob $job) use ($employee) {
            return $job->connection === 'deferred'
                && $job->queue === 'integrations'
                && $job->operation === 'employee'
                && $job->payload['empleado_id'] === $employee->id;
        });
    }

    public function test_csv_import_falls_back_to_deferred_when_no_worker_is_available(): void
    {
        Queue::fake();
        Storage::fake('local');
        Cache::forget('system:queue-heartbeat');
        config()->set('queue.workload_connections.imports', 'auto');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('asistencias.importar'), [
                'archivo_reloj' => UploadedFile::fake()->createWithContent(
                    'reloj.csv',
                    "No.,Nombre,Fecha/Hora\n1,EMPLEADO PRUEBA,2026-07-01 08:00:00"
                ),
                'idempotency_key' => 'csv-deferred-test',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('operation_id');

        Queue::assertPushed(PrepareAttendanceImportJob::class, function (PrepareAttendanceImportJob $job) {
            return $job->connection === 'deferred' && $job->queue === 'imports';
        });

        $this->assertDatabaseHas('system_operations', [
            'type' => 'attendance_import_preview',
            'status' => 'queued',
            'progress' => 1,
        ]);
    }

    public function test_csv_import_uses_database_queue_when_worker_heartbeat_is_current(): void
    {
        config()->set('queue.workload_connections.imports', 'auto');
        config()->set('queue.default', 'database');
        Cache::put('system:queue-heartbeat', now()->toISOString(), now()->addMinutes(10));

        $this->assertSame('database', app(SystemOperationService::class)->queueConnection('imports'));
    }

    public function test_payroll_pdf_falls_back_to_deferred_when_no_export_worker_is_available(): void
    {
        Queue::fake();
        Cache::forget('system:queue-heartbeat');
        config()->set('queue.workload_connections.exports', 'auto');
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('nominas.exportaciones.store'), [
                'export_type' => 'payroll_pdf',
                'fecha_corte' => '2026-07-29',
                'empleado_ids' => [],
                'idempotency_key' => 'payroll-pdf-deferred-test',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('operation_id');

        Queue::assertPushed(GenerateMassPdfJob::class, function (GenerateMassPdfJob $job) {
            return $job->connection === 'deferred' && $job->queue === 'exports';
        });

        $this->assertDatabaseHas('system_operations', [
            'type' => 'mass_export',
            'status' => 'queued',
        ]);
    }

    public function test_payroll_preflight_only_blocks_critical_employees_in_the_selected_pdf_scope(): void
    {
        $included = $this->employee([
            'numero_empleado' => '507',
            'sueldo_semanal' => 2200,
            'sueldo_por_hora' => 0,
        ]);
        $this->employee([
            'numero_empleado' => '508',
            'sueldo_semanal' => 0,
            'sueldo_por_hora' => 0,
        ]);
        $start = \Carbon\Carbon::parse('2026-07-23');
        $end = \Carbon\Carbon::parse('2026-07-29');

        foreach (\Carbon\CarbonPeriod::create($start, $end) as $date) {
            if (!HorarioLaboralEmpleado::esDiaLaboral($included, $date)) {
                continue;
            }

            Asistencia::create([
                'empleado_id' => $included->id,
                'fecha' => $date->toDateString(),
                'tipo_asistencia' => 'Falta',
                'minutos_tarde' => 0,
                'horas_trabajadas' => 0,
                'horas_extra' => 0,
            ]);
        }

        $preflight = app(PayrollPreflightService::class);

        $this->assertFalse($preflight->inspect($start, $end)['ready']);
        $this->assertTrue($preflight->inspect($start, $end, [$included->id])['ready']);
    }

    public function test_stale_operations_are_cancelled_hidden_and_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'admin']);
        $operations = app(SystemOperationService::class);
        $stale = $operations->create('attendance_import_preview', $user);
        $other = $operations->create('attendance_import_preview', $otherUser);
        $stale->forceFill(['updated_at' => now()->subMinutes(20)])->saveQuietly();

        $recent = $operations->recentFor($user);

        $this->assertTrue($recent->isEmpty());
        $this->assertSame('cancelled', $stale->fresh()->status);
        $this->assertSame('queued', $other->fresh()->status);
    }

    public function test_finished_operation_can_be_dismissed_from_notifications(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $operation = SystemOperation::create([
            'user_id' => $user->id,
            'type' => 'attendance_import_preview',
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Lista.',
            'finished_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('operaciones.dismiss', $operation))
            ->assertOk()
            ->assertJson(['dismissed' => true]);

        $this->assertSame('dismissed', $operation->fresh()->status);
    }

    public function test_firebase_rules_are_valid_and_restrict_each_employee_to_their_own_data(): void
    {
        $rules = json_decode(
            file_get_contents(base_path('firebase/database.rules.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        )['rules'];

        $this->assertArrayNotHasKey('.read', $rules);
        $this->assertArrayNotHasKey('.write', $rules);
        $this->assertSame('auth != null && auth.uid === $uid', $rules['usuarios']['$uid']['.read']);
        $this->assertStringContainsString(
            "child('empleado_id_key').val() === \$empleadoId",
            $rules['empleados']['$empleadoId']['.read']
        );
        $this->assertFalse($rules['usuarios']['$uid']['.write']);
        $this->assertFalse($rules['empleados']['$empleadoId']['.write']);
    }

    private function employee(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(500, 999),
            'nombre_completo' => 'Empleado Escalabilidad',
            'puesto' => 'GENERAL',
            'forma_pago' => 'Efectivo',
            'fecha_ingreso' => '2026-01-01',
            'sueldo_semanal' => 2000,
            'sueldo_por_hora' => 0,
            'cuota_prestamo' => 0,
            'saldo_prestamo' => 0,
            'descuento_imss' => 0,
            'descuento_isr' => 0,
            'descuento_infonavit' => 0,
            'estatus' => true,
            'es_estudiante' => false,
        ], $overrides));
    }
}
