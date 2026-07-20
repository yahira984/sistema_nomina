<?php

namespace Tests\Feature;

use App\Http\Controllers\AsistenciaController;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Support\HorarioLaboralEmpleado;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AsistenciaHorariosEspecialesTest extends TestCase
{
    use RefreshDatabase;

    public function test_domingo_trabajado_por_personal_general_se_calcula_como_extra(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
        ]);

        $resultado = $this->calcularHoras('2026-07-19', '08:00', '14:00', $empleado);

        $this->assertSame(0.0, (float) $resultado['horas_trabajadas']);
        $this->assertSame(6.0, (float) $resultado['horas_extra']);
        $this->assertSame(0, $resultado['minutos_tarde']);
    }

    public function test_asistencia_entre_semana_conserva_media_hora_extra(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
        ]);

        $resultado = $this->calcularHoras('2026-07-20', '08:00', '18:00', $empleado);

        $this->assertSame(9.5, (float) $resultado['horas_trabajadas']);
        $this->assertSame(0.5, (float) $resultado['horas_extra']);
    }

    public function test_media_hora_de_estudiante_se_suma_una_sola_vez_a_sus_horas_normales(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '150',
            'puesto' => 'ESTUDIANTE',
            'es_estudiante' => true,
            'sueldo_semanal' => 0,
            'sueldo_por_hora' => 50,
        ]);

        $resultado = $this->calcularHoras('2026-07-20', '08:00', '18:00', $empleado);

        $this->assertSame(10.0, (float) $resultado['horas_trabajadas']);
        $this->assertSame(0.0, (float) $resultado['horas_extra']);
    }

    public function test_turno_de_vigilancia_cruza_medianoche_sin_retardo_ni_horas_extra(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '120',
            'puesto' => 'VIGILANCIA-SEGURIDAD',
            'fecha_ingreso' => '2026-07-16',
        ]);

        $resultado = $this->calcularHoras('2026-07-16', '08:00', '08:00', $empleado);

        $this->assertSame(24.0, (float) $resultado['horas_trabajadas']);
        $this->assertSame(0.0, (float) $resultado['horas_extra']);
        $this->assertSame(0, $resultado['minutos_tarde']);
    }

    public function test_nomina_incluye_el_domingo_trabajado_como_pago_extra(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
            'sueldo_semanal' => 5600,
        ]);

        foreach (['2026-07-16', '2026-07-17', '2026-07-20', '2026-07-21', '2026-07-22'] as $fecha) {
            $this->registrarAsistencia($empleado, $fecha, '08:00', '17:30');
        }

        $this->registrarAsistencia($empleado, '2026-07-19', '08:00', '14:00');
        $nomina = $this->calcularNomina($empleado, '2026-07-16', '2026-07-22');

        $this->assertSame(6.0, (float) $nomina['horas_extra_pagadas']);
        $this->assertSame(1200.0, (float) $nomina['pago_extra']);
        $this->assertSame(6800.0, (float) $nomina['total_percepciones']);
    }

    public function test_nomina_paga_correctamente_media_hora_extra(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
            'sueldo_semanal' => 5600,
        ]);

        foreach (['2026-07-16', '2026-07-20', '2026-07-21', '2026-07-22'] as $fecha) {
            $this->registrarAsistencia($empleado, $fecha, '08:00', '17:30');
        }

        $this->registrarAsistencia($empleado, '2026-07-17', '08:00', '18:00');
        $nomina = $this->calcularNomina($empleado, '2026-07-16', '2026-07-22');

        $this->assertSame(0.5, (float) $nomina['horas_extra_pagadas']);
        $this->assertSame(100.0, (float) $nomina['pago_extra']);
        $this->assertSame(5700.0, (float) $nomina['total_percepciones']);
    }

    public function test_nomina_vigilancia_completa_paga_sueldo_semanal_sin_retardos_ni_extras(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '120',
            'puesto' => 'VIGILANCIA-SEGURIDAD',
            'fecha_ingreso' => '2026-07-16',
            'sueldo_semanal' => 2230,
        ]);

        foreach (HorarioLaboralEmpleado::fechasLaborales(
            $empleado,
            Carbon::parse('2026-07-16'),
            Carbon::parse('2026-07-22')
        ) as $fecha) {
            $this->registrarAsistencia($empleado, $fecha, '08:00', '08:00');
        }

        $nomina = $this->calcularNomina($empleado, '2026-07-16', '2026-07-22');

        $this->assertSame(4, $nomina['dias_requeridos_asistencia']);
        $this->assertSame(0.0, (float) $nomina['horas_extra_pagadas']);
        $this->assertSame(0, $nomina['minutos_tarde_descontables']);
        $this->assertSame(2230.0, (float) $nomina['pago_normal']);
    }

    public function test_preview_muestra_faltas_del_rango_y_no_genera_faltas_de_fin_de_semana(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
        ]);

        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => '2026-07-17',
            'tipo_asistencia' => 'Falta',
            'minutos_tarde' => 0,
            'horas_trabajadas' => 0,
            'horas_extra' => 0,
        ]);

        $preview = $this->crearPreview([
            ['50', 'EMPLEADO PRUEBA', '2026-07-20', '08:00'],
            ['50', 'EMPLEADO PRUEBA', '2026-07-20', '17:30'],
        ], '2026-07-16', '2026-07-22');

        $filasEmpleado = collect($preview['filas'])->where('empleado_id', $empleado->id)->values();
        $faltas = $filasEmpleado->where('tipo_asistencia', 'Falta');

        $this->assertSame('2026-07-16', $preview['resumen']['fecha_inicio']);
        $this->assertSame('2026-07-22', $preview['resumen']['fecha_fin']);
        $this->assertCount(5, $filasEmpleado);
        $this->assertCount(4, $faltas);
        $this->assertTrue($faltas->contains(fn (array $fila) => $fila['fecha'] === '2026-07-17' && $fila['estado'] === 'existente'));
        $this->assertFalse($faltas->contains(fn (array $fila) => in_array($fila['fecha'], ['2026-07-18', '2026-07-19'], true)));
    }

    public function test_preview_recalcula_media_hora_en_un_registro_existente(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
        ]);

        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => '2026-07-20',
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => '08:00',
            'hora_salida' => '18:00',
            'minutos_tarde' => 0,
            'horas_trabajadas' => 9.5,
            'horas_extra' => 0,
        ]);

        $preview = $this->crearPreview([], '2026-07-20', '2026-07-20');
        $fila = collect($preview['filas'])->firstWhere('empleado_id', $empleado->id);

        $this->assertNotNull($fila);
        $this->assertSame('existente', $fila['estado']);
        $this->assertSame(0.5, (float) $fila['horas_extra']);
    }

    public function test_preview_vigilancia_empareja_salida_del_dia_siguiente_y_deja_incompletas_editables(): void
    {
        $empleado = $this->crearEmpleado([
            'numero_empleado' => '120',
            'puesto' => 'VIGILANCIA-SEGURIDAD',
            'fecha_ingreso' => '2026-07-16',
        ]);

        $preview = $this->crearPreview([
            ['120', 'VIGILANTE PRUEBA', '2026-07-16', '08:00'],
            ['120', 'VIGILANTE PRUEBA', '2026-07-17', '08:00'],
            ['120', 'VIGILANTE PRUEBA', '2026-07-18', '08:00'],
            ['120', 'VIGILANTE PRUEBA', '2026-07-19', '08:00'],
            ['120', 'VIGILANTE PRUEBA', '2026-07-20', '08:00'],
        ], '2026-07-16', '2026-07-22');

        $filas = collect($preview['filas'])->where('empleado_id', $empleado->id)->keyBy('fecha');

        $this->assertSame(24.0, (float) $filas['2026-07-16']['horas_trabajadas']);
        $this->assertSame(0.0, (float) $filas['2026-07-16']['horas_extra']);
        $this->assertSame('incompleta', $filas['2026-07-20']['estado']);
        $this->assertFalse($filas['2026-07-20']['aprobado']);
        $this->assertSame('Falta', $filas['2026-07-22']['tipo_asistencia']);
        $this->assertArrayNotHasKey('2026-07-17', $filas->all());
        $this->assertArrayNotHasKey('2026-07-19', $filas->all());
    }

    public function test_preview_agrupa_dobles_con_pocos_minutos_sin_inventar_una_salida(): void
    {
        $metodo = new ReflectionMethod(AsistenciaController::class, 'resolverHorarioMarcajes');
        $controlador = app(AsistenciaController::class);

        $soloEntradaDoble = $metodo->invoke($controlador, '2026-07-20', ['08:15', '08:16']);
        $jornadaConEntradaDoble = $metodo->invoke($controlador, '2026-07-20', ['08:15', '08:16', '17:30']);

        $this->assertTrue($soloEntradaDoble['incompleta']);
        $this->assertSame('08:15', $soloEntradaDoble['hora_entrada']);
        $this->assertNull($soloEntradaDoble['hora_salida']);
        $this->assertSame(1, $soloEntradaDoble['marcas']);
        $this->assertFalse($jornadaConEntradaDoble['incompleta']);
        $this->assertSame('17:30', $jornadaConEntradaDoble['hora_salida']);
        $this->assertSame(2, $jornadaConEntradaDoble['marcas']);
    }

    private function calcularHoras(string $fecha, string $entrada, string $salida, Empleado $empleado): array
    {
        $metodo = new ReflectionMethod(AsistenciaController::class, 'calcularHoras');

        return $metodo->invoke(app(AsistenciaController::class), $fecha, $entrada, $salida, 'Normal', $empleado);
    }

    private function calcularNomina(Empleado $empleado, string $inicio, string $fin): array
    {
        $metodo = new ReflectionMethod(\App\Http\Controllers\NominaController::class, 'calcularDesgloseNomina');

        return $metodo->invoke(
            app(\App\Http\Controllers\NominaController::class),
            $empleado,
            Carbon::parse($inicio),
            Carbon::parse($fin)
        );
    }

    private function registrarAsistencia(Empleado $empleado, string $fecha, string $entrada, string $salida): void
    {
        Asistencia::create(array_merge([
            'empleado_id' => $empleado->id,
            'fecha' => $fecha,
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => $entrada,
            'hora_salida' => $salida,
        ], $this->calcularHoras($fecha, $entrada, $salida, $empleado)));
    }

    private function crearPreview(array $filasCsv, ?string $inicio = null, ?string $fin = null): array
    {
        $path = tempnam(sys_get_temp_dir(), 'reloj_');
        $archivo = fopen($path, 'w');

        foreach ($filasCsv as $fila) {
            fputcsv($archivo, $fila);
        }

        fclose($archivo);

        try {
            $metodo = new ReflectionMethod(AsistenciaController::class, 'prepararRevisionImportacion');

            return $metodo->invoke(app(AsistenciaController::class), $path, $inicio, $fin);
        } finally {
            unlink($path);
        }
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(100, 999),
            'nombre_completo' => 'EMPLEADO PRUEBA',
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
            'banco' => 'Efectivo',
            'estatus' => true,
            'es_estudiante' => false,
            'ajuste_vacaciones' => 0,
        ], $overrides));
    }
}
