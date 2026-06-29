<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use App\Support\SemanaNomina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominaAsistenciaPendienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_nomina_is_not_generated_when_week_has_no_attendance_records(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '2',
            'nombre_completo' => 'Alcantara Patino Angel',
            'sueldo_semanal' => 2200,
        ]);

        $this->actingAs($admin)
            ->get(route('nominas.generar', [
                'empleado_id' => $empleado->id,
                'fecha_corte' => '2026-06-24',
            ]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('nominas', [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => '2026-06-18',
            'fecha_fin' => '2026-06-24',
        ]);
    }

    public function test_imss_deposit_does_not_create_nomina_when_attendance_is_pending(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado();

        $this->actingAs($admin)
            ->put(route('nominas.diferencia-imss.update', $empleado), [
                'fecha_corte' => '2026-06-24',
                'deposito_imss' => 1800,
            ])
            ->assertSessionHasErrors('asistencia');

        $this->assertDatabaseMissing('nominas', [
            'empleado_id' => $empleado->id,
        ]);
    }

    public function test_nomina_can_be_generated_when_labor_days_are_captured(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado([
            'sueldo_semanal' => 2200,
        ]);
        $this->crearAsistenciasLaborables($empleado, '2026-06-24');

        $this->actingAs($admin)
            ->put(route('nominas.ajustes', $empleado), [
                'fecha_corte' => '2026-06-24',
                'prestamo_descuento' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nominas', [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => '2026-06-18',
            'fecha_fin' => '2026-06-24',
            'pago_neto' => 2200,
        ]);
    }

    public function test_nomina_can_be_generated_with_at_least_one_attendance_record_in_week(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado([
            'sueldo_semanal' => 2200,
        ]);

        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => '2026-06-18',
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => '08:00',
            'hora_salida' => '17:30',
            'minutos_tarde' => 0,
            'horas_trabajadas' => 9.5,
            'horas_extra' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('nominas.ajustes', $empleado), [
                'fecha_corte' => '2026-06-24',
                'prestamo_descuento' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nominas', [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => '2026-06-18',
            'fecha_fin' => '2026-06-24',
            'pago_neto' => 2200,
        ]);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(100, 999),
            'nombre_completo' => 'Empleado Prueba',
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

    private function crearAsistenciasLaborables(Empleado $empleado, string $fechaCorte): void
    {
        [$inicioSemana, $finSemana] = SemanaNomina::desdeCorte($fechaCorte);
        $cursor = $inicioSemana->copy();

        while ($cursor->lte($finSemana)) {
            if (!$cursor->isWeekend()) {
                Asistencia::create([
                    'empleado_id' => $empleado->id,
                    'fecha' => $cursor->format('Y-m-d'),
                    'tipo_asistencia' => 'Normal',
                    'hora_entrada' => '08:00',
                    'hora_salida' => '17:30',
                    'minutos_tarde' => 0,
                    'horas_trabajadas' => 9.5,
                    'horas_extra' => 0,
                ]);
            }

            $cursor->addDay();
        }
    }
}
