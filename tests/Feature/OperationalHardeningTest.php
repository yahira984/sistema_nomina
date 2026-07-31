<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Services\AttendanceImportQualityService;
use App\Services\PayrollPreflightService;
use App\Services\ReceiptHistoryService;
use App\Services\WorkRuleResolver;
use App\Models\WorkRule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_preflight_blocks_missing_salary_and_missing_attendance(): void
    {
        $employee = $this->employee(['sueldo_semanal' => 0, 'sueldo_por_hora' => 0]);

        $result = app(PayrollPreflightService::class)->inspect(
            Carbon::parse('2026-07-16'),
            Carbon::parse('2026-07-22')
        );

        $this->assertFalse($result['ready']);
        $this->assertGreaterThan(0, $result['critical_count']);
        $this->assertContains('missing_salary', collect($result['issues'])->pluck('code'));
        $this->assertContains('missing_attendance', collect($result['issues'])->pluck('code'));
    }

    public function test_preflight_accepts_a_fully_captured_regular_week(): void
    {
        $employee = $this->employee();
        foreach (['2026-07-16', '2026-07-17', '2026-07-20', '2026-07-21', '2026-07-22'] as $date) {
            Asistencia::create([
                'empleado_id' => $employee->id,
                'fecha' => $date,
                'tipo_asistencia' => 'Normal',
                'hora_entrada' => '08:00',
                'hora_salida' => '17:30',
                'horas_trabajadas' => 9.5,
                'horas_extra' => 0,
                'minutos_tarde' => 0,
            ]);
        }

        $result = app(PayrollPreflightService::class)->inspect(Carbon::parse('2026-07-16'), Carbon::parse('2026-07-22'));

        $this->assertTrue($result['ready']);
        $this->assertSame(0, $result['critical_count']);
    }

    public function test_import_quality_flags_incomplete_and_repeated_marks(): void
    {
        $quality = app(AttendanceImportQualityService::class)->summarize([
            ['estado' => 'incompleta', 'tipo_asistencia' => 'Normal', 'marcas' => 1, 'hora_entrada' => '08:00', 'hora_salida' => null],
            ['estado' => 'detectada', 'tipo_asistencia' => 'Normal', 'marcas' => 4, 'hora_entrada' => '08:00', 'hora_salida' => '17:30', 'horas_trabajadas' => 9.5],
        ]);

        $this->assertSame(1, $quality['incomplete']);
        $this->assertSame(1, $quality['duplicate_marks']);
        $this->assertSame('critical', $quality['status']);
    }

    public function test_receipt_generation_is_recorded_independently_from_payroll(): void
    {
        $employee = $this->employee();
        app(ReceiptHistoryService::class)->record(
            'payroll_individual', Carbon::parse('2026-07-16'), Carbon::parse('2026-07-22'),
            'recibo.pdf', employeeId: $employee->id, content: 'pdf-content'
        );

        $this->assertDatabaseHas('receipt_generations', [
            'empleado_id' => $employee->id,
            'type' => 'payroll_individual',
            'file_name' => 'recibo.pdf',
            'receipt_count' => 1,
        ]);
    }

    public function test_many_employees_resolve_rules_with_one_rules_query(): void
    {
        WorkRule::create([
            'name' => 'Regla global', 'scope_type' => 'global', 'sin_retardos' => true,
            'priority' => 100, 'active' => true,
        ]);
        $employees = collect(range(1, 120))->map(fn ($number) => $this->employee(['numero_empleado' => (string) (1000 + $number)]));
        WorkRuleResolver::forget();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $employees->each(fn (Empleado $employee) => WorkRuleResolver::for($employee));
        $ruleQueries = collect(DB::getQueryLog())->filter(function ($query) {
            $sql = strtolower($query['query']);
            return str_contains($sql, ' from "work_rules"') || str_contains($sql, ' from `work_rules`');
        });

        $this->assertCount(1, $ruleQueries);
    }

    private function employee(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(100, 999),
            'nombre_completo' => 'Empleado Operativo',
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
