<?php

namespace Tests\Unit;

use App\Support\DiasLaborados;
use PHPUnit\Framework\TestCase;

class DiasLaboradosTest extends TestCase
{
    public function test_cuenta_dias_inclusivos_sin_domingos(): void
    {
        $this->assertSame(6, DiasLaborados::contarSinDomingos('2026-06-29', '2026-07-05'));
    }

    public function test_cuenta_solo_el_anio_de_la_baja_sin_domingos(): void
    {
        $this->assertSame(2, DiasLaborados::contarAnioDeBaja('2026-12-30', '2027-01-03'));
    }
}
