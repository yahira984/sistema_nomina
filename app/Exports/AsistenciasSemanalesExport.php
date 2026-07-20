<?php

namespace App\Exports;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Support\HorasExtraEmpleado;
use App\Support\ReglasNominaEmpleado;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsistenciasSemanalesExport implements FromArray, WithColumnWidths, WithDrawings, WithEvents, WithTitle
{
    private const COLUMN_COUNT = 32;
    private const FIRST_DATA_ROW = 6;
    private const DAYS = ['JUEVES', 'VIERNES', 'SABADO', 'DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES'];

    private array $cellStatuses = [];
    private int $lastDataRow = self::FIRST_DATA_ROW - 1;

    public function __construct(
        private readonly Carbon $inicioSemana,
        private readonly Carbon $finSemana,
    ) {
    }

    public function array(): array
    {
        $this->cellStatuses = [];
        $asistencias = $this->asistenciasPorEmpleadoFecha();
        $empleados = Empleado::where('estatus', true)
            ->orderByRaw("CAST(COALESCE(NULLIF(numero_empleado, ''), NULLIF(numero_empleado_baja, ''), id) AS UNSIGNED) ASC")
            ->orderBy('nombre_completo')
            ->get();

        $rows = [
            $this->emptyRow(),
            $this->headerTitleRow(),
            $this->headerPeriodRow(),
            $this->dayHeaderRow(),
            $this->subHeaderRow(),
        ];

        foreach ($empleados as $empleado) {
            $dataRowNumber = count($rows) + 1;
            $rows[] = $this->employeeRow($empleado, $asistencias, $dataRowNumber);
            $rows[] = $this->signatureRow();
        }

        $this->lastDataRow = count($rows);

        if ($empleados->isEmpty()) {
            $rows[] = $this->emptyMessageRow();
            $this->lastDataRow = count($rows);
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Semana ' . $this->inicioSemana->isoWeek();
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 6,
            'B' => 34,
            'AE' => 10,
            'AF' => 10,
        ];

        for ($index = 3; $index <= 30; $index++) {
            $widths[Coordinate::stringFromColumnIndex($index)] = 8;
        }

        return $widths;
    }

    public function drawings(): array
    {
        $drawings = [];
        $logos = [
            ['path' => public_path('img/promatec-pdf.jpg'), 'cell' => 'A1', 'height' => 38],
            ['path' => public_path('img/lugarth-pdf.jpg'), 'cell' => 'B1', 'height' => 38],
        ];

        foreach ($logos as $logo) {
            if (!file_exists($logo['path'])) {
                continue;
            }

            $drawing = new Drawing();
            $drawing->setPath($logo['path']);
            $drawing->setCoordinates($logo['cell']);
            $drawing->setHeight($logo['height']);
            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->styleSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function styleSheet(Worksheet $sheet): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(self::COLUMN_COUNT);

        foreach ([1, 2, 3] as $row) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
        }

        $sheet->mergeCells("A4:A5");
        $sheet->mergeCells("B4:B5");

        $column = 3;
        foreach (self::DAYS as $_day) {
            $start = Coordinate::stringFromColumnIndex($column);
            $end = Coordinate::stringFromColumnIndex($column + 3);
            $sheet->mergeCells("{$start}4:{$end}4");
            $column += 4;
        }

        $sheet->mergeCells('AE4:AF4');
        $sheet->getStyle("A1:{$lastColumn}3")->applyFromArray([
            'font' => ['name' => 'Arial', 'bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$lastColumn}5")->applyFromArray([
            'font' => ['name' => 'Arial', 'bold' => true, 'size' => 8],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F8FAFC']],
        ]);

        $sheet->getStyle("A" . self::FIRST_DATA_ROW . ":{$lastColumn}{$this->lastDataRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 8],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '000000']]],
        ]);

        foreach ($this->cellStatuses as $status) {
            $start = Coordinate::stringFromColumnIndex($status['start']);
            $end = Coordinate::stringFromColumnIndex($status['end']);
            $fill = match ($status['type']) {
                'Falta' => 'FF0000',
                'Incapacidad' => 'FFF2CC',
                'Vacaciones' => 'D9EAD3',
                default => null,
            };

            if ($fill) {
                $sheet->getStyle("{$start}{$status['row']}:{$end}{$status['row']}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $fill]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        for ($row = self::FIRST_DATA_ROW + 1; $row <= $this->lastDataRow; $row += 2) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 8],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F8FAFC']],
            ]);
        }

        $sheet->getStyle("A" . self::FIRST_DATA_ROW . ":B{$this->lastDataRow}")->getFont()->setBold(true);
        $sheet->getStyle("A" . self::FIRST_DATA_ROW . ":A{$this->lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C" . self::FIRST_DATA_ROW . ":{$lastColumn}{$this->lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension(1)->setRowHeight(35);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(18);

        for ($row = self::FIRST_DATA_ROW; $row <= $this->lastDataRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight($row % 2 === 0 ? 24 : 18);
        }

        $sheet->freezePane('C6');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.25);
        $sheet->getPageMargins()->setBottom(0.25);
        $sheet->getPageMargins()->setLeft(0.2);
        $sheet->getPageMargins()->setRight(0.2);
    }

    private function asistenciasPorEmpleadoFecha(): array
    {
        return Asistencia::whereBetween('fecha', [
            $this->inicioSemana->format('Y-m-d'),
            $this->finSemana->format('Y-m-d'),
        ])
            ->get()
            ->keyBy(fn (Asistencia $asistencia) => $asistencia->empleado_id . '|' . Carbon::parse($asistencia->fecha)->format('Y-m-d'))
            ->all();
    }

    private function headerTitleRow(): array
    {
        return $this->rowWith([
            1 => 'LUGARTH- PROMATEC    2026',
        ]);
    }

    private function headerPeriodRow(): array
    {
        return $this->rowWith([
            1 => 'NOMINA PACHUCA - SEMANA No. ' . $this->inicioSemana->isoWeek()
                . ' - SEMANA DEL ' . $this->inicioSemana->format('d/m/Y')
                . ' AL ' . $this->finSemana->format('d/m/Y'),
        ]);
    }

    private function dayHeaderRow(): array
    {
        $row = $this->emptyRow();
        $row[0] = 'No.';
        $row[1] = 'NOMBRE';

        $column = 2;
        foreach ($this->days() as $day) {
            $row[$column] = $day['label'];
            $column += 4;
        }

        $row[30] = 'TOTAL';

        return $row;
    }

    private function subHeaderRow(): array
    {
        $row = $this->emptyRow();
        $column = 2;

        foreach (self::DAYS as $_day) {
            $row[$column] = 'ENT';
            $row[$column + 1] = 'SAL';
            $row[$column + 2] = 'H.E.';
            $row[$column + 3] = 'RET';
            $column += 4;
        }

        $row[30] = 'H.E.';
        $row[31] = 'RET';

        return $row;
    }

    private function employeeRow(Empleado $empleado, array $asistencias, int $rowNumber): array
    {
        $row = $this->emptyRow();
        $row[0] = $empleado->numero_empleado ?: ($empleado->numero_empleado_baja ?: 'S/N');
        $row[1] = strtoupper($empleado->nombre_completo);
        $esEstudiante = (bool) ($empleado->es_estudiante ?? false);
        $totalExtra = 0;
        $totalRetardo = 0;
        $column = 2;

        foreach ($this->days() as $day) {
            $asistencia = $asistencias[$empleado->id . '|' . $day['date']] ?? null;

            if (!$asistencia) {
                $column += 4;
                continue;
            }

            if ($asistencia->tipo_asistencia === 'Normal') {
                $horasExtra = $esEstudiante ? 0 : $this->horasExtraParaExportar($asistencia, $empleado);
                $minutosTarde = $esEstudiante ? 0 : (int) $asistencia->minutos_tarde;
                $row[$column] = $this->shortTime($asistencia->hora_entrada);
                $row[$column + 1] = $this->shortTime($asistencia->hora_salida);
                $row[$column + 2] = $this->formatNumber($horasExtra);
                $row[$column + 3] = $minutosTarde;
            } else {
                $horasExtra = 0;
                $minutosTarde = 0;
                $row[$column] = strtoupper($asistencia->tipo_asistencia);
                $this->cellStatuses[] = [
                    'row' => $rowNumber,
                    'start' => $column + 1,
                    'end' => $column + 4,
                    'type' => $asistencia->tipo_asistencia,
                ];
            }

            $totalExtra += $horasExtra;
            $totalRetardo += $minutosTarde;
            $column += 4;
        }

        $row[30] = $this->formatNumber($totalExtra);
        $row[31] = $totalRetardo;

        return $row;
    }

    private function horasExtraParaExportar(Asistencia $asistencia, Empleado $empleado): float
    {
        if (ReglasNominaEmpleado::sinHorasExtra($empleado)) {
            return 0;
        }

        if (!$asistencia->hora_entrada || !$asistencia->hora_salida) {
            return 0;
        }

        return HorasExtraEmpleado::calcular(
            $empleado,
            $asistencia->fecha,
            $asistencia->hora_entrada,
            $asistencia->hora_salida
        );
    }

    private function signatureRow(): array
    {
        return $this->rowWith([
            2 => 'FIRMA',
        ]);
    }

    private function emptyMessageRow(): array
    {
        return $this->rowWith([
            1 => 'SIN EMPLEADOS ACTIVOS',
        ]);
    }

    private function days(): array
    {
        $days = [];

        foreach (self::DAYS as $index => $name) {
            $date = $this->inicioSemana->copy()->addDays($index);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $name . ' ' . $date->format('d'),
            ];
        }

        return $days;
    }

    private function rowWith(array $values): array
    {
        $row = $this->emptyRow();

        foreach ($values as $column => $value) {
            $row[$column - 1] = $value;
        }

        return $row;
    }

    private function emptyRow(): array
    {
        return array_fill(0, self::COLUMN_COUNT, '');
    }

    private function shortTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '';
    }

    private function formatNumber(float $number): string
    {
        if (abs($number) < 0.001) {
            return '';
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
