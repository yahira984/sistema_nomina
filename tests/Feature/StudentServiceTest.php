<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use App\Services\StudentServiceSummaryService;
use App\Services\StudentTerminationLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_completed_remaining_and_progress_hours(): void
    {
        $student = $this->student(['horas_servicio_requeridas' => 100, 'fecha_inicio_servicio' => '2026-08-01']);
        Asistencia::create(['empleado_id' => $student->id, 'fecha' => '2026-08-03', 'tipo_asistencia' => 'Normal', 'hora_entrada' => '08:00', 'hora_salida' => '13:00', 'horas_trabajadas' => 5, 'horas_extra' => 0]);
        Asistencia::create(['empleado_id' => $student->id, 'fecha' => '2026-08-04', 'tipo_asistencia' => 'Normal', 'hora_entrada' => '08:00', 'hora_salida' => '14:30', 'horas_trabajadas' => 6.5, 'horas_extra' => 0]);
        Asistencia::create(['empleado_id' => $student->id, 'fecha' => '2026-07-31', 'tipo_asistencia' => 'Normal', 'horas_trabajadas' => 8, 'horas_extra' => 0]);

        $summary = app(StudentServiceSummaryService::class)->forEmployee($student);

        $this->assertSame(11.5, $summary['horas_cumplidas']);
        $this->assertSame(88.5, $summary['horas_restantes']);
        $this->assertSame(11.5, $summary['porcentaje']);
        $this->assertSame(2, $summary['dias_con_registro']);
        $this->assertSame('En curso', $summary['estado']);
    }

    public function test_student_academic_and_termination_data_can_be_updated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = $this->student();

        $this->actingAs($admin)->patch(route('empleados.servicio-alumno.actualizar', $student), [
            'universidad' => 'Universidad Politecnica de Pachuca',
            'carrera' => 'Ingenieria Industrial',
            'matricula_estudiante' => '2431124669',
            'encargado_estadias_escuela' => 'MTRA. ALDA NELLY FERNANDEZ SANCHEZ',
            'horas_servicio_requeridas' => 480,
            'fecha_inicio_servicio' => '2026-08-01',
            'fecha_termino_servicio' => '2026-11-01',
            'evaluacion_estadia' => 9.07,
            'area_proyecto_servicio' => 'Produccion',
            'matricula_estudiante' => '2431124669',
            'encargado_estadias_escuela' => 'MTRA. ALDA NELLY FERNANDEZ SANCHEZ',
            'observaciones_servicio' => 'Turno matutino',
            'servicio_pausado' => false,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('empleados', [
            'id' => $student->id,
            'universidad' => 'Universidad Politecnica de Pachuca',
            'horas_servicio_requeridas' => 480,
            'area_proyecto_servicio' => 'Produccion',
        ]);
    }

    public function test_it_generates_an_editable_letter_without_changing_template_assets(): void
    {
        $student = $this->student([
            'nombre_completo' => 'Marco Dashiell Ortega Hernandez',
            'universidad' => 'Universidad Politecnica de Pachuca',
            'carrera' => 'Ingenieria Mecatronica',
            'matricula_estudiante' => '2431124669',
            'encargado_estadias_escuela' => 'MTRA. ALDA NELLY FERNANDEZ SANCHEZ',
            'horas_servicio_requeridas' => 310,
            'fecha_inicio_servicio' => '2026-05-26',
            'fecha_termino_servicio' => '2026-08-26',
            'area_proyecto_servicio' => 'Grua de canastilla elevadora Terex LT 40',
            'evaluacion_estadia' => 9.07,
        ]);
        Asistencia::create([
            'empleado_id' => $student->id,
            'fecha' => '2026-05-27',
            'tipo_asistencia' => 'Normal',
            'hora_entrada' => '08:00',
            'hora_salida' => '16:00',
            'horas_trabajadas' => 8,
            'horas_extra' => 0,
        ]);

        $summary = app(StudentServiceSummaryService::class)->forEmployee($student);
        $document = app(StudentTerminationLetterService::class)->generate($student, $summary);

        $generated = new ZipArchive;
        $this->assertTrue($generated->open($document['path']) === true);
        $xml = $generated->getFromName('word/document.xml');
        $this->assertStringContainsString('Marco Dashiell Ortega Hernandez', $xml);
        $this->assertStringContainsString('2431124669', $xml);
        $this->assertStringContainsString('________________________________', $xml);
        $this->assertStringNotContainsString('{{', $xml);

        $template = new ZipArchive;
        $this->assertTrue($template->open(resource_path('templates/carta_termino_servicio_social.docx')) === true);
        for ($index = 0; $index < $template->numFiles; $index++) {
            $name = $template->getNameIndex($index);
            if ($name !== 'word/document.xml') {
                $this->assertSame($template->getFromName($name), $generated->getFromName($name), "El componente {$name} debe conservarse sin cambios.");
            }
        }
        $template->close();
        $generated->close();
        @unlink($document['path']);
    }

    private function student(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'numero_empleado' => (string) fake()->unique()->numberBetween(700, 999),
            'nombre_completo' => 'Alumno de Prueba',
            'puesto' => 'ESTUDIANTE',
            'forma_pago' => 'Efectivo',
            'fecha_ingreso' => '2026-08-01',
            'sueldo_semanal' => 0,
            'sueldo_por_hora' => 27,
            'estatus' => true,
            'es_estudiante' => true,
        ], $overrides));
    }
}
