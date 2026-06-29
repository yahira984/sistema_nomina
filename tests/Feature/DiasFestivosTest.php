<?php

namespace Tests\Feature;

use App\Models\DiaFestivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiasFestivosTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_holiday_can_be_deleted_permanently(): void
    {
        $admin = User::factory()->create();
        $dia = DiaFestivo::create([
            'fecha' => '2026-06-15',
            'nombre' => 'Descanso interno',
            'tipo' => 'empresa',
            'es_oficial' => false,
            'activo' => true,
            'origen' => 'manual',
        ]);

        $this->actingAs($admin)
            ->delete(route('dias-festivos.destroy', $dia))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('dias_festivos', ['id' => $dia->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'dia_festivo.deleted']);
    }

    public function test_system_holiday_is_deactivated_instead_of_deleted(): void
    {
        $admin = User::factory()->create();
        $dia = DiaFestivo::create([
            'fecha' => '2026-12-25',
            'nombre' => 'Navidad',
            'tipo' => 'oficial',
            'es_oficial' => true,
            'activo' => true,
            'origen' => 'sistema',
        ]);

        $this->actingAs($admin)
            ->delete(route('dias-festivos.destroy', $dia))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dias_festivos', [
            'id' => $dia->id,
            'activo' => false,
            'origen' => 'sistema',
        ]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'dia_festivo.updated']);
    }
}
