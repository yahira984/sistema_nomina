<?php

namespace Tests\Feature;

use App\Http\Controllers\AsistenciaController;
use App\Models\Asistencia;
use App\Models\DiaFestivo;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominaDiasFestivosTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_absence_is_paid_as_normal_day_and_not_discounted_as_absence(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado(['sueldo_semanal' => 2100]);

        $this->crearDiaFestivo('2026-09-16');
        $this->crearAsistencia($empleado, '2026-09-10');
        $this->crearFalta($empleado, '2026-09-16');

        $this->actingAs($admin)
            ->put(route('nominas.ajustes', $empleado), [
                'fecha_corte' => '2026-09-16',
                'prestamo_descuento' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nominas', [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-16',
            'total_percepciones' => 2100,
            'total_deducciones' => 0,
            'pago_neto' => 2100,
        ]);
    }

    public function test_worked_holiday_adds_one_extra_day_payment_for_weekly_employee(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado(['sueldo_semanal' => 2100]);

        $this->crearDiaFestivo('2026-09-16');
        $this->crearAsistencia($empleado, '2026-09-10');
        $this->crearAsistencia($empleado, '2026-09-16', '08:00', '17:30', 9.5);

        $this->actingAs($admin)
            ->put(route('nominas.ajustes', $empleado), [
                'fecha_corte' => '2026-09-16',
                'prestamo_descuento' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nominas', [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-16',
            'dias_festivos_trabajados' => 1,
            'horas_festivas_trabajadas' => 9.5,
            'pago_festivo_trabajado' => 300,
            'total_percepciones' => 2400,
            'total_deducciones' => 0,
            'pago_neto' => 2400,
        ]);
    }

    public function test_clock_import_labor_days_exclude_active_holidays(): void
    {
        $this->crearDiaFestivo('2026-09-16');

        $controller = app(AsistenciaController::class);
        $method = new \ReflectionMethod($controller, 'diasLaborales');
        $method->setAccessible(true);

        $dias = $method->invoke(
            $controller,
            \Carbon\Carbon::parse('2026-09-14'),
            \Carbon\Carbon::parse('2026-09-16')
        );

        $this->assertSame(['2026-09-14', '2026-09-15'], $dias);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(100, 999),
            'nombre_completo' => 'Empleado Festivo',
            'puesto' => 'General',
            'forma_pago' => 'Transferencia',
            'fecha_ingreso' => '2026-01-01',
            'sueldo_semanal' => 2000,
            'sueldo_por_hora' => 0,
            'cuota_prestamo' => 0,
            'saldo_prestamo' => 0,
            'descuento_imss' => 0,
            'descuento_isr' => 0,
            'descuento_infonavit' => 0,
            'banco' => 'Azteca',
            'numero_cuenta' => '1234567890',
            'estatus' => true,
            'es_estudiante' => false,
        ], $overrides));
    }

    private function crearDiaFestivo(string $fecha): void
    {
        DiaFestivo::create([
            'fecha' => $fecha,
            'nombre' => 'Festivo prueba',
            'tipo' => 'oficial',
            'es_oficial' => true,
            'activo' => true,
            'origen' => 'manual',
        ]);
    }

    private function crearAsistencia(
        Empleado $empleado,
        string $fecha,
        string $entrada = '08:00',
        string $salida = '17:30',
        float $horas = 9.5
    ): void {
        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => $fecha,
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
            'minutos_tarde' => 0,
            'horas_trabajadas' => $horas,
            'horas_extra' => 0,
        ]);
    }

    private function crearFalta(Empleado $empleado, string $fecha): void
    {
        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => $fecha,
            'tipo_asistencia' => 'Falta',
            'hora_entrada' => null,
            'hora_salida' => null,
            'minutos_tarde' => 0,
            'horas_trabajadas' => 0,
            'horas_extra' => 0,
        ]);
    }
}
