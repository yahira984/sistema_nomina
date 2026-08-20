<?php

namespace Tests\Unit;

use App\Models\Empleado;
use App\Models\Asistencia;
use App\Services\FirebaseSyncService;
use App\Support\HorasExtraEmpleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class HorasExtraEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('horariosEntreSemana')]
    public function test_entre_semana_aplica_tolerancia_maxima_de_siete_minutos(string $salida, float $esperado): void
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
            'faltan ocho minutos' => ['17:52', 0.0],
            'faltan siete minutos' => ['17:53', 0.5],
            'media hora exacta' => ['18:00', 0.5],
            'faltan ocho minutos para una hora' => ['18:22', 0.5],
            'faltan siete minutos para una hora' => ['18:23', 1.0],
            'una hora exacta' => ['18:30', 1.0],
        ];
    }

    #[DataProvider('horariosFinDeSemana')]
    public function test_fin_de_semana_aplica_la_misma_tolerancia_de_siete_minutos(string $salida, float $esperado): void
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
            'faltan ocho minutos para cinco y media' => ['13:22', 5.0],
            'faltan siete minutos para cinco y media' => ['13:23', 5.5],
            'faltan ocho minutos para seis' => ['13:52', 5.5],
            'faltan siete minutos para seis' => ['13:53', 6.0],
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
