<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPuntualidadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_shows_top_three_punctual_and_impunctual_without_students_saturdays_or_exempt_employees(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-29 10:00:00'));

        $admin = User::factory()->create();

        $luis = $this->crearEmpleado('1', 'LUIS PUNTUAL');
        $ana = $this->crearEmpleado('2', 'ANA PUNTUAL');
        $marco = $this->crearEmpleado('3', 'MARCO PUNTUAL');
        $pedro = $this->crearEmpleado('4', 'PEDRO TARDE');
        $juan = $this->crearEmpleado('5', 'JUAN TARDE');
        $omar = $this->crearEmpleado('6', 'OMAR TARDE');
        $estudiante = $this->crearEmpleado('7', 'ALUMNO EXCLUIDO', ['es_estudiante' => true]);
        $exento = $this->crearEmpleado('14', 'EXENTO RETARDOS');
        $sabado = $this->crearEmpleado('15', 'SABADO EXCLUIDO');

        $this->registrarNormal($luis, '2026-06-18', '07:45:00');
        $this->registrarNormal($luis, '2026-06-19', '07:45:00');
        $this->registrarNormal($luis, '2026-06-22', '08:00:00');

        $this->registrarNormal($ana, '2026-06-18', '07:50:00');
        $this->registrarNormal($ana, '2026-06-19', '08:00:00');
        $this->registrarNormal($ana, '2026-06-22', '07:55:00');

        $this->registrarNormal($marco, '2026-06-18', '07:50:00');
        $this->registrarNormal($marco, '2026-06-19', '07:50:00');

        $this->registrarNormal($pedro, '2026-06-18', '08:45:00', 45);
        $this->registrarNormal($pedro, '2026-06-19', '08:15:00', 15);
        $this->registrarNormal($juan, '2026-06-18', '08:30:00', 30);
        $this->registrarNormal($omar, '2026-06-18', '08:10:00', 10);

        $this->registrarNormal($estudiante, '2026-06-18', '07:00:00');
        $this->registrarNormal($estudiante, '2026-06-19', '10:00:00', 120);
        $this->registrarNormal($exento, '2026-06-18', '07:00:00');
        $this->registrarNormal($exento, '2026-06-19', '10:00:00', 120);
        $this->registrarNormal($sabado, '2026-06-20', '10:00:00', 120);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('tempranoControl.semana.ranking', 3)
                ->where('tempranoControl.semana.ranking.0.numero_empleado', '1')
                ->where('tempranoControl.semana.ranking.0.dias', 3)
                ->where('tempranoControl.semana.ranking.1.numero_empleado', '2')
                ->where('tempranoControl.semana.ranking.2.numero_empleado', '3')
                ->has('retardosControl.semana.ranking', 3)
                ->where('retardosControl.semana.lider.numero_empleado', '4')
                ->where('retardosControl.semana.ranking.0.minutos', 60)
                ->where('retardosControl.semana.ranking.1.numero_empleado', '5')
                ->where('retardosControl.semana.ranking.2.numero_empleado', '6')
            );
    }

    private function crearEmpleado(string $numero, string $nombre, array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => $numero,
            'nombre_completo' => $nombre,
            'puesto' => 'OPERADOR',
            'fecha_ingreso' => '2026-01-01',
            'forma_pago' => 'Efectivo',
            'sueldo_semanal' => 2000,
            'sueldo_por_hora' => 0,
            'cuota_prestamo' => 0,
            'saldo_prestamo' => 0,
            'descuento_imss' => 0,
            'descuento_isr' => 0,
            'descuento_infonavit' => 0,
            'banco' => 'Efectivo',
            'estatus' => true,
            'es_estudiante' => false,
            'ajuste_vacaciones' => 0,
        ], $overrides));
    }

    private function registrarNormal(Empleado $empleado, string $fecha, string $entrada, int $minutosTarde = 0): void
    {
        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => $fecha,
            'tipo_asistencia' => 'Normal',
            'minutos_tarde' => $minutosTarde,
            'hora_entrada' => $entrada,
            'hora_salida' => '16:00:00',
            'horas_trabajadas' => 8,
            'horas_extra' => 0,
        ]);
    }
}
