<?php

namespace Tests\Unit;

use App\Models\Empleado;
use App\Models\Asistencia;
use App\Services\FirebaseSyncService;
use App\Support\HorasExtraEmpleado;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class HorasExtraEmpleadoTest extends TestCase
{
    #[DataProvider('horariosEntreSemana')]
    public function test_entre_semana_cuenta_bloques_completos_de_media_hora(string $salida, float $esperado): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '50',
            'puesto' => 'GENERAL',
        ]);

        $resultado = HorasExtraEmpleado::calcular($empleado, '2026-07-20', '08:00', $salida);

        $this->assertSame($esperado, $resultado);
    }

    public static function horariosEntreSemana(): array
    {
        return [
            'menos de media hora' => ['17:59', 0.0],
            'media hora exacta' => ['18:00', 0.5],
            'media hora con minutos adicionales' => ['18:29', 0.5],
            'una hora exacta' => ['18:30', 1.0],
        ];
    }

    #[DataProvider('horariosFinDeSemana')]
    public function test_fin_de_semana_redondea_a_la_media_hora_mas_cercana(string $salida, float $esperado): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '50',
            'puesto' => 'GENERAL',
        ]);

        $resultado = HorasExtraEmpleado::calcular($empleado, '2026-07-19', '08:00', $salida);

        $this->assertSame($esperado, $resultado);
    }

    public static function horariosFinDeSemana(): array
    {
        return [
            'antes del cuarto de hora' => ['13:14', 5.0],
            'desde el cuarto de hora' => ['13:15', 5.5],
            'antes de tres cuartos' => ['13:44', 5.5],
            'desde tres cuartos' => ['13:45', 6.0],
        ];
    }

    #[DataProvider('empleadosSinHorasExtra')]
    public function test_respeta_empleados_que_no_generan_horas_extra(string $numero, string $puesto): void
    {
        $empleado = new Empleado([
            'numero_empleado' => $numero,
            'puesto' => $puesto,
        ]);

        $resultado = HorasExtraEmpleado::calcular($empleado, '2026-07-20', '08:00', '19:00');

        $this->assertSame(0.0, $resultado);
    }

    public static function empleadosSinHorasExtra(): array
    {
        return [
            'empleado 8' => ['8', 'GENERAL'],
            'empleado 9' => ['9', 'GENERAL'],
            'empleado 22' => ['22', 'GENERAL'],
            'vigilancia por puesto' => ['120', 'VIGILANCIA-SEGURIDAD'],
        ];
    }

    public function test_payload_de_firebase_envia_la_media_hora_recalculada(): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '50',
            'puesto' => 'GENERAL',
            'es_estudiante' => false,
        ]);
        $asistencia = new Asistencia([
            'fecha' => '2026-07-20',
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => '08:00',
            'hora_salida' => '18:00',
            'horas_trabajadas' => 9.5,
            'horas_extra' => 0,
        ]);
        $asistencia->setRelation('empleado', $empleado);

        $metodo = new ReflectionMethod(FirebaseSyncService::class, 'datosAsistencia');
        $payload = $metodo->invoke(null, $asistencia);

        $this->assertSame(0.5, $payload['horas_extra']);
        $this->assertSame(9.5, $payload['horas_trabajadas']);
    }
}
