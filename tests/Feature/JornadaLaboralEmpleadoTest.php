<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\WorkRule;
use App\Services\WorkRuleResolver;
use App\Support\HorarioLaboralEmpleado;
use App\Support\HorasExtraEmpleado;
use App\Support\JornadaLaboralEmpleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JornadaLaboralEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        WorkRuleResolver::forget();
        parent::tearDown();
    }

    public function test_late_arrival_and_early_departure_are_accumulated(): void
    {
        $empleado = $this->empleado();

        $this->assertSame(40, JornadaLaboralEmpleado::minutosIncidencia(
            $empleado,
            '2026-08-17',
            '08:10',
            '17:00'
        ));
    }

    public function test_overtime_rounds_up_only_when_seven_minutes_or_less_are_missing(): void
    {
        $empleado = $this->empleado();

        $this->assertSame(1.0, HorasExtraEmpleado::calcular($empleado, '2026-08-20', '08:00', '18:24'));
        $this->assertSame(0.5, HorasExtraEmpleado::calcular($empleado, '2026-08-20', '08:00', '18:22'));
        $this->assertSame(6, JornadaLaboralEmpleado::minutosIncidencia($empleado, '2026-08-20', '08:00', '18:24'));
        $this->assertSame(0, JornadaLaboralEmpleado::minutosIncidencia($empleado, '2026-08-20', '08:00', '18:22'));
    }

    public function test_overtime_tolerance_is_added_to_other_daily_delay_minutes(): void
    {
        $empleado = $this->empleado();

        $this->assertSame(16, JornadaLaboralEmpleado::minutosIncidencia(
            $empleado,
            '2026-08-20',
            '08:10',
            '18:24'
        ));
    }

    public function test_week_example_accumulates_twenty_eight_minutes_without_saturday_arrival_delay(): void
    {
        $empleado = $this->empleado();
        $marcaciones = [
            ['2026-08-20', '08:01', '18:24'],
            ['2026-08-21', '08:02', '18:29'],
            ['2026-08-22', '08:11', '14:36'],
            ['2026-08-24', '08:00', '19:34'],
            ['2026-08-25', '08:06', '19:40'],
            ['2026-08-26', '08:07', '20:30'],
        ];

        $minutos = collect($marcaciones)->sum(fn (array $marca) => JornadaLaboralEmpleado::minutosIncidencia(
            $empleado,
            $marca[0],
            $marca[1],
            $marca[2]
        ));

        $this->assertSame(28, $minutos);
        $this->assertSame(5, JornadaLaboralEmpleado::minutosIncidencia(
            $empleado,
            '2026-08-22',
            '08:11',
            '14:36'
        ));
    }

    public function test_guard_attendance_on_cycle_rest_day_is_detected_as_normal_extra_day(): void
    {
        $empleado = $this->empleado();
        WorkRule::create([
            'name' => 'Vigilancia 24x24 prueba',
            'scope_type' => 'employee',
            'empleado_id' => $empleado->id,
            'turno_24x24' => true,
            'sin_horas_extra' => true,
            'sin_retardos' => true,
            'fecha_referencia_turno' => '2026-08-17',
            'priority' => 100,
            'active' => true,
        ]);
        WorkRuleResolver::forget();

        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-08-17'));
        $this->assertFalse(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-08-18'));
        $this->assertTrue(JornadaLaboralEmpleado::esDescansoTrabajado24x24(
            $empleado,
            '2026-08-18',
            '08:00',
            '08:00'
        ));
    }

    private function empleado(): Empleado
    {
        return Empleado::create([
            'numero_empleado' => '991',
            'nombre_completo' => 'Empleado Jornada',
            'puesto' => 'General',
            'forma_pago' => 'Transferencia',
            'fecha_ingreso' => '2026-01-01',
            'sueldo_semanal' => 2100,
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
        ]);
    }
}
