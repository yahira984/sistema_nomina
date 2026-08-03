<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use App\Support\DiasLaborados;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeReentryAndPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_restore_records_reentry_and_resets_seniority_vacation_period(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee([
            'estatus' => false,
            'fecha_ingreso' => '2020-01-10',
            'fecha_baja' => '2026-06-30',
            'numero_empleado' => null,
            'numero_empleado_baja' => '80',
            'ajuste_vacaciones' => 4,
        ]);

        Asistencia::create([
            'empleado_id' => $employee->id,
            'fecha' => '2026-05-12',
            'tipo_asistencia' => 'Vacaciones',
            'minutos_tarde' => 0,
            'horas_trabajadas' => 0,
            'horas_extra' => 0,
        ]);

        $this->actingAs($admin)
            ->put(route('empleados.restaurar', $employee), [
                'fecha_reingreso' => '2026-07-15',
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertTrue((bool) $employee->estatus);
        $this->assertSame('2020-01-10', $employee->fecha_ingreso);
        $this->assertSame('2026-07-15', $employee->fecha_reingreso);
        $this->assertSame('2026-07-15', $employee->fecha_inicio_periodo_actual);
        $this->assertSame(0, $employee->antiguedad_anios);
        $this->assertSame(0.0, $employee->dias_vacaciones_tomados);
        $this->assertSame(0, (int) $employee->ajuste_vacaciones);
        $this->assertDatabaseHas('empleado_reingresos', [
            'empleado_id' => $employee->id,
            'fecha_reingreso' => '2026-07-15',
            'fecha_baja_anterior' => '2026-06-30',
            'registrado_por' => $admin->id,
        ]);

        $employee->forceFill(['fecha_baja' => '2026-07-31'])->save();
        $this->assertSame(
            DiasLaborados::contarSinDomingos('2026-07-15', '2026-07-31'),
            $employee->fresh()->dias_laborados
        );
    }

    public function test_restore_rejects_date_not_after_last_termination(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee([
            'estatus' => false,
            'fecha_baja' => '2026-07-20',
            'numero_empleado' => null,
            'numero_empleado_baja' => '81',
        ]);

        $this->actingAs($admin)
            ->from(route('empleados.index'))
            ->put(route('empleados.restaurar', $employee), [
                'fecha_reingreso' => '2026-07-20',
            ])
            ->assertRedirect(route('empleados.index'))
            ->assertSessionHasErrors('fecha_reingreso');

        $this->assertFalse((bool) $employee->fresh()->estatus);
        $this->assertDatabaseCount('empleado_reingresos', 0);
    }

    public function test_replacing_photo_removes_previous_employee_files(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee(['numero_empleado' => '82']);
        $originalPublicPath = public_path();
        $temporaryPublicPath = storage_path('framework/testing/photos-' . Str::uuid());
        app()->usePublicPath($temporaryPublicPath);

        try {
            $directory = public_path('img/empleados');
            File::ensureDirectoryExists($directory);
            File::put($directory . "/id-{$employee->id}.jpg", 'old-id-photo');
            File::put($directory . '/82.jpeg', 'old-number-photo');
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

            $this->actingAs($admin)
                ->post(route('empleados.foto.actualizar', $employee), [
                    'foto' => UploadedFile::fake()->createWithContent('nueva.png', $png),
                ])
                ->assertSessionHasNoErrors();

            $this->assertFileExists($directory . '/82.png');
            $this->assertFileDoesNotExist($directory . "/id-{$employee->id}.jpg");
            $this->assertFileDoesNotExist($directory . '/82.jpeg');
            $this->assertSame($png, File::get($directory . '/82.png'));
        } finally {
            app()->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryPublicPath);
        }
    }

    public function test_changing_employee_number_renames_photo_and_removes_previous_name(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee(['numero_empleado' => '83']);
        $originalPublicPath = public_path();
        $temporaryPublicPath = storage_path('framework/testing/photos-' . Str::uuid());
        app()->usePublicPath($temporaryPublicPath);

        try {
            $directory = public_path('img/empleados');
            File::ensureDirectoryExists($directory);
            File::put($directory . '/83.jpg', 'employee-photo');

            $this->actingAs($admin)
                ->put(route('empleados.update', $employee), array_merge($employee->only([
                    'nombre_completo', 'puesto', 'fecha_ingreso', 'forma_pago', 'banco', 'numero_cuenta',
                    'nss', 'rfc', 'curp', 'estado_civil', 'genero', 'fecha_nacimiento', 'telefono', 'correo',
                    'direccion', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
                ]), [
                    'numero_empleado' => '84',
                    'es_estudiante' => false,
                    'sueldo_semanal' => 2000,
                ]))
                ->assertSessionHasNoErrors();

            $this->assertFileDoesNotExist($directory . '/83.jpg');
            $this->assertFileExists($directory . '/84.jpg');
            $this->assertSame('employee-photo', File::get($directory . '/84.jpg'));
        } finally {
            app()->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryPublicPath);
        }
    }

    private function employee(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(500, 999),
            'nombre_completo' => 'Empleado Reingreso',
            'puesto' => 'GENERAL',
            'forma_pago' => 'Efectivo',
            'fecha_ingreso' => '2024-01-10',
            'sueldo_semanal' => 2000,
            'sueldo_por_hora' => 0,
            'cuota_prestamo' => 0,
            'saldo_prestamo' => 0,
            'descuento_imss' => 0,
            'descuento_isr' => 0,
            'descuento_infonavit' => 0,
            'ajuste_vacaciones' => 0,
            'estatus' => true,
            'es_estudiante' => false,
        ], $overrides));
    }
}
