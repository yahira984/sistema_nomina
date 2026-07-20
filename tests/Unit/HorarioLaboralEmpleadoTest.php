<?php

namespace Tests\Unit;

use App\Models\Empleado;
use App\Support\HorarioLaboralEmpleado;
use PHPUnit\Framework\TestCase;

class HorarioLaboralEmpleadoTest extends TestCase
{
    public function test_personal_general_solo_tiene_dias_laborales_de_lunes_a_viernes(): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '50',
            'puesto' => 'SOLDADOR',
            'fecha_ingreso' => '2026-01-01',
        ]);

        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-17'));
        $this->assertFalse(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-18'));
        $this->assertFalse(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-19'));
    }

    public function test_vigilancia_trabaja_turnos_alternados_incluso_en_fin_de_semana(): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '120',
            'puesto' => 'Vigilancia-Seguridad',
            'fecha_ingreso' => '2026-07-16',
        ]);

        $this->assertTrue(HorarioLaboralEmpleado::esVigilancia24x24($empleado));
        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-16'));
        $this->assertFalse(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-17'));
        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-18'));
        $this->assertTrue(HorarioLaboralEmpleado::esDiaLaboral($empleado, '2026-07-20'));
    }

    public function test_empleados_20_y_29_conservan_el_respaldo_de_vigilancia(): void
    {
        $empleado = new Empleado([
            'numero_empleado' => '20',
            'puesto' => null,
            'fecha_ingreso' => '2026-01-01',
        ]);

        $this->assertTrue(HorarioLaboralEmpleado::esVigilancia24x24($empleado));
    }
}
