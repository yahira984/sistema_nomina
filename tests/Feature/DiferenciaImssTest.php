<?php

namespace Tests\Feature;

use App\Exports\DiferenciaImssExport;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\User;
use App\Support\SemanaNomina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DiferenciaImssTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposito_imss_is_saved_without_changing_payroll_net_amount(): void
    {
        $admin = User::factory()->create();
        $empleado = $this->crearEmpleado([
            'sueldo_semanal' => 2200,
        ]);
        $this->crearAsistenciasLaborables($empleado, '2026-06-10');

        $this->actingAs($admin)
            ->put(route('nominas.diferencia-imss.update', $empleado), [
                'fecha_corte' => '2026-06-10',
                'deposito_imss' => 1800.50,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('nominas', [
            'empleado_id' => $empleado->id,
            'pago_neto' => 2200,
            'deposito_imss' => 1800.50,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'nomina.updated',
        ]);
    }

    public function test_diferencia_imss_export_only_includes_nominas_with_deposito_imss(): void
    {
        $admin = User::factory()->create();
        [$inicioSemana, $finSemana, $numeroSemana] = SemanaNomina::desdeCorte('2026-06-10');
        $empleadoConImss = $this->crearEmpleado([
            'numero_empleado' => '14',
            'nombre_completo' => 'Islas Mayoral Ricardo',
            'banco' => 'Santander',
        ]);
        $empleadoSinImss = $this->crearEmpleado([
            'numero_empleado' => '15',
            'nombre_completo' => 'Empleado Sin Imss',
            'banco' => 'Azteca',
        ]);

        Nomina::create([
            'empleado_id' => $empleadoConImss->id,
            'numero_semana' => $numeroSemana,
            'fecha_inicio' => $inicioSemana->format('Y-m-d'),
            'fecha_fin' => $finSemana->format('Y-m-d'),
            'pago_neto' => 1924.30,
            'deposito_imss' => 1824.22,
        ]);
        Nomina::create([
            'empleado_id' => $empleadoSinImss->id,
            'numero_semana' => $numeroSemana,
            'fecha_inicio' => $inicioSemana->format('Y-m-d'),
            'fecha_fin' => $finSemana->format('Y-m-d'),
            'pago_neto' => 1500,
            'deposito_imss' => 0,
        ]);

        $contenido = Excel::raw(
            new DiferenciaImssExport($numeroSemana, $inicioSemana, $finSemana),
            \Maatwebsite\Excel\Excel::XLSX
        );

        $this->assertNotEmpty($contenido);

        Excel::fake();

        $this->actingAs($admin)
            ->get(route('nominas.diferencia-imss', [
                'semana' => $numeroSemana,
                'fecha_corte' => $finSemana->format('Y-m-d'),
            ]));

        $nombreArchivo = 'Diferencia_IMSS_Semana_' . $numeroSemana . '_' . $inicioSemana->format('Ymd') . '_' . $finSemana->format('Ymd') . '.xlsx';

        Excel::assertDownloaded($nombreArchivo, function (DiferenciaImssExport $export) use ($empleadoConImss) {
            $filas = $export->collection();
            $fila = $export->map($filas->first());

            return $filas->count() === 1
                && $filas->first()->empleado_id === $empleadoConImss->id
                && $fila[3] === 1924.30
                && $fila[4] === 1824.22
                && $fila[5] === 100.08
                && $fila[6] === 1924.30;
        });
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
            'banco' => 'Santander',
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
