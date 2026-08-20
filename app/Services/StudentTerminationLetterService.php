<?php

namespace App\Services;

use App\Models\Empleado;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class StudentTerminationLetterService
{
    private const TEMPLATE = 'templates/carta_termino_servicio_social.docx';

    public function generate(Empleado $employee, array $summary): array
    {
        $this->validate($employee);

        $template = resource_path(self::TEMPLATE);
        if (! is_file($template)) {
            throw new RuntimeException('No se encontró la plantilla oficial de la carta de término.');
        }

        $directory = storage_path('app/private/exports/student-letters');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la carpeta de cartas.');
        }

        $employeeNumber = $employee->numero_empleado ?: $employee->numero_empleado_baja ?: $employee->id;
        $filename = 'carta_termino_'.$employeeNumber.'_'.Str::slug($employee->nombre_completo).'.docx';
        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.docx';

        if (! copy($template, $path)) {
            throw new RuntimeException('No fue posible copiar la plantilla oficial.');
        }

        try {
            $this->replaceDocumentData($path, [
                '{{FECHA_CARTA}}' => $this->spanishDate(now()),
                '{{ENCARGADO_ESCUELA}}' => Str::upper($employee->encargado_estadias_escuela),
                '{{UNIVERSIDAD}}' => Str::upper($employee->universidad),
                '{{NOMBRE_ALUMNO}}' => $employee->nombre_completo,
                '{{CARRERA}}' => $employee->carrera,
                '{{MATRICULA}}' => $employee->matricula_estudiante,
                '{{PROYECTO}}' => Str::upper($employee->area_proyecto_servicio),
                '{{FECHA_INICIO}}' => $this->spanishDate($employee->fecha_inicio_servicio),
                '{{FECHA_TERMINO}}' => $this->spanishDate($employee->fecha_termino_servicio),
                '{{EVALUACION}}' => rtrim(rtrim(number_format((float) $employee->evaluacion_estadia, 2, '.', ''), '0'), '.'),
                '{{HORAS_CUMPLIDAS}}' => $this->formatHours((float) ($summary['horas_cumplidas'] ?? 0)),
            ]);
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }

        return compact('path', 'filename');
    }

    private function validate(Empleado $employee): void
    {
        if (! (bool) $employee->es_estudiante) {
            throw ValidationException::withMessages(['carta' => 'La carta solo está disponible para alumnos.']);
        }

        $labels = [
            'universidad' => 'universidad',
            'carrera' => 'carrera',
            'matricula_estudiante' => 'matrícula',
            'encargado_estadias_escuela' => 'encargado de estadías de la escuela',
            'fecha_inicio_servicio' => 'fecha de inicio',
            'fecha_termino_servicio' => 'fecha de término',
            'area_proyecto_servicio' => 'área o proyecto',
            'evaluacion_estadia' => 'evaluación',
        ];
        $missing = collect($labels)
            ->filter(fn ($label, $field) => blank($employee->{$field}))
            ->values()
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'carta' => 'Completa antes de generar la carta: '.implode(', ', $missing).'.',
            ]);
        }
    }

    private function replaceDocumentData(string $path, array $replacements): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('No fue posible abrir la carta editable.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('La plantilla no contiene un documento Word válido.');
        }

        foreach ($replacements as $token => $value) {
            $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml = str_replace($token, $escaped, $xml, $count);
            if ($count !== 1) {
                $zip->close();
                throw new RuntimeException("La plantilla oficial no contiene el campo {$token} esperado.");
            }
        }

        if (str_contains($xml, '{{')) {
            $zip->close();
            throw new RuntimeException('La carta conserva campos sin completar.');
        }

        if (! $zip->addFromString('word/document.xml', $xml)) {
            $zip->close();
            throw new RuntimeException('No fue posible escribir los datos en la carta.');
        }
        $zip->close();
    }

    private function spanishDate(CarbonInterface|string $date): string
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return $date->day.' de '.$months[$date->month].' de '.$date->year;
    }

    private function formatHours(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
    }
}
