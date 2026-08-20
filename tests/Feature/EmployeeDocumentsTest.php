<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\DatabaseBackupService;
use App\Services\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class EmployeeDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_is_compressed_and_replacing_it_removes_the_previous_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee();

        $this->actingAs($admin)->post(route('empleados.documentos.store', $employee), [
            'document_type' => 'ine',
            'file' => UploadedFile::fake()->image('ine-original.jpg', 3200, 1800),
        ])->assertSessionHasNoErrors();

        $document = EmployeeDocument::where('empleado_id', $employee->id)->where('document_type', 'ine')->firstOrFail();
        $firstPath = $document->path;
        Storage::disk('local')->assertExists($firstPath);
        $this->assertSame('webp', $document->extension);
        $this->assertSame('image/webp', $document->mime_type);
        $image = imagecreatefromstring(Storage::disk('local')->get($firstPath));
        $this->assertNotFalse($image);
        $this->assertLessThanOrEqual(2000, max(imagesx($image), imagesy($image)));
        imagedestroy($image);

        $this->actingAs($admin)->post(route('empleados.documentos.store', $employee), [
            'document_type' => 'ine',
            'file' => UploadedFile::fake()->image('ine-reemplazo.png', 1200, 1600),
        ])->assertSessionHasNoErrors();

        $replacement = $document->fresh();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($replacement->path);
        $this->assertDatabaseCount('employee_documents', 1);
    }

    public function test_private_documents_require_the_dedicated_permission(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $viewer = User::factory()->create(['role' => 'consulta', 'permissions' => ['empleados.view']]);
        $employee = $this->employee();
        $document = EmployeeDocument::create([
            'empleado_id' => $employee->id,
            'uploaded_by' => $admin->id,
            'document_type' => 'acta_nacimiento',
            'original_name' => 'acta.pdf',
            'stored_name' => 'acta.pdf',
            'path' => "employees/{$employee->id}/documents/acta.pdf",
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'original_size_bytes' => 12,
            'stored_size_bytes' => 12,
            'checksum' => hash('sha256', 'pdf-content'),
        ]);
        Storage::disk('local')->put($document->path, 'pdf-content');

        $this->actingAs($viewer)
            ->get(route('empleados.documentos.view', [$employee, $document]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('empleados.documentos.view', [$employee, $document]))
            ->assertOk();
    }

    public function test_health_report_lists_missing_documents_and_required_employee_data(): void
    {
        $employee = $this->employee([
            'curp' => null,
            'nss' => null,
            'telefono' => null,
            'contacto_emergencia_nombre' => null,
            'contacto_emergencia_telefono' => null,
            'rfc' => null,
            'fecha_ingreso' => null,
        ]);

        $inconsistencies = app(SystemHealthService::class)->inconsistencies();
        $documents = collect($inconsistencies['missing_documents'])->firstWhere('id', $employee->id);
        $data = collect($inconsistencies['missing_employee_data'])->firstWhere('id', $employee->id);

        $this->assertSame(8, $documents['missing_count']);
        $this->assertSame(7, $data['missing_count']);
        $this->assertContains('CURP', $data['missing']);
        $this->assertContains('Número de Seguro Social', $data['missing']);
    }

    public function test_integral_archive_restores_documents_and_photos_and_exposes_its_sql(): void
    {
        Storage::fake('local');
        $originalPublicPath = public_path();
        $temporaryPublicPath = storage_path('framework/testing/backup-assets-'.Str::uuid());
        app()->usePublicPath($temporaryPublicPath);

        try {
            $archivePath = Storage::disk('local')->path('respaldo.zip');
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
            $sql = "-- Respaldo generado por Sistema de Nominas\nSET FOREIGN_KEY_CHECKS=1;\n";
            $zip->addFromString('database.sql', $sql);
            $zip->addFromString('employee-documents/7/documents/ine.webp', 'document-content');
            $zip->addFromString('employee-photos/7.jpg', 'photo-content');
            $zip->close();

            $service = app(DatabaseBackupService::class);
            $this->assertSame($sql, $service->sqlFromPath($archivePath, 'zip'));
            $restored = $service->restoreAssetsFromArchive($archivePath);

            $this->assertSame(['documents' => 1, 'photos' => 1], $restored);
            Storage::disk('local')->assertExists('employees/7/documents/ine.webp');
            $this->assertSame('photo-content', File::get(public_path('img/empleados/7.jpg')));
        } finally {
            app()->usePublicPath($originalPublicPath);
            File::deleteDirectory($temporaryPublicPath);
        }
    }

    public function test_personal_data_can_be_completed_from_profile_without_changing_payroll_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = $this->employee([
            'curp' => null,
            'nss' => null,
            'rfc' => null,
            'telefono' => null,
            'contacto_emergencia_nombre' => null,
            'contacto_emergencia_telefono' => null,
            'fecha_ingreso' => null,
            'sueldo_semanal' => 2750,
        ]);

        $this->actingAs($admin)
            ->patch(route('empleados.datos-personales.actualizar', $employee), [
                'curp' => 'aakc020117hmcxvn01',
                'nss' => '12345678901',
                'rfc' => 'aakc020117abc',
                'telefono' => '7711234567',
                'contacto_emergencia_nombre' => 'Contacto Prueba',
                'contacto_emergencia_telefono' => '7717654321',
                'fecha_ingreso' => '2024-02-01',
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('AAKC020117HMCXVN01', $employee->curp);
        $this->assertSame('AAKC020117ABC', $employee->rfc);
        $this->assertSame('2024-02-01', $employee->fecha_ingreso);
        $this->assertSame(2750.0, (float) $employee->sueldo_semanal);
        $this->assertSame('GENERAL', $employee->puesto);
    }

    public function test_personal_data_quick_editor_requires_employee_management_permission(): void
    {
        $viewer = User::factory()->create(['role' => 'consulta', 'permissions' => ['empleados.view']]);
        $employee = $this->employee(['telefono' => null]);

        $this->actingAs($viewer)
            ->patch(route('empleados.datos-personales.actualizar', $employee), [
                'telefono' => '7711234567',
            ])
            ->assertForbidden();

        $this->assertNull($employee->fresh()->telefono);
    }

    private function employee(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => 'DOC-'.fake()->unique()->numberBetween(100, 999),
            'nombre_completo' => 'Empleado Documental',
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
