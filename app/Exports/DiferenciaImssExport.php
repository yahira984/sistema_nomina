<?php

namespace App\Exports;

use App\Models\Nomina;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DiferenciaImssExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    private const LAST_COLUMN = 'G';
    private const FIRST_DATA_ROW = 3;

    private Collection $filas;

    public function __construct(
        private int $semana,
        private Carbon $inicioSemana,
        private Carbon $finSemana,
    ) {
        $this->filas = collect();
    }

    public function collection(): Collection
    {
        $this->filas = Nomina::with('empleado')
            ->whereDate('fecha_inicio', $this->inicioSemana->format('Y-m-d'))
            ->whereDate('fecha_fin', $this->finSemana->format('Y-m-d'))
            ->where('deposito_imss', '>', 0)
            ->get()
            ->sort(function (Nomina $a, Nomina $b) {
                $empleadoA = $a->empleado;
                $empleadoB = $b->empleado;

                return [
                    strtoupper((string) ($empleadoA?->banco ?? '')),
                    (int) ($empleadoA?->numero_empleado ?? $empleadoA?->numero_empleado_baja ?? 0),
                    strtoupper((string) ($empleadoA?->nombre_completo ?? '')),
                ] <=> [
                    strtoupper((string) ($empleadoB?->banco ?? '')),
                    (int) ($empleadoB?->numero_empleado ?? $empleadoB?->numero_empleado_baja ?? 0),
                    strtoupper((string) ($empleadoB?->nombre_completo ?? '')),
                ];
            })
            ->values();

        return $this->filas;
    }

    public function headings(): array
    {
        return [
            ['RELACION CONCENTRADA DE DEPOSITOS ' . $this->inicioSemana->year],
            [
                'No.',
                'Banco',
                'Nombre',
                'Importe semanal real',
                'Depositos IMSS',
                'Diferencia semana',
                'Suma total depositos',
            ],
        ];
    }

    public function map($nomina): array
    {
        $empleado = $nomina->empleado;
        $pagoNeto = round((float) ($nomina->pago_neto ?? 0), 2);
        $depositoImss = round((float) ($nomina->deposito_imss ?? 0), 2);
        $diferenciaSemana = round($pagoNeto - $depositoImss, 2);

        return [
            $empleado?->numero_empleado ?? $empleado?->numero_empleado_baja ?? 'S/N',
            strtoupper((string) ($empleado?->banco ?? '')),
            strtoupper((string) ($empleado?->nombre_completo ?? 'SIN EMPLEADO')),
            $pagoNeto,
            $depositoImss,
            $diferenciaSemana,
            round($depositoImss + $diferenciaSemana, 2),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '$#,##0.00;-$#,##0.00',
            'E' => '$#,##0.00;-$#,##0.00',
            'F' => '$#,##0.00;-$#,##0.00',
            'G' => '$#,##0.00;-$#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:' . self::LAST_COLUMN . '1');

        $sheet->getStyle('A1:' . self::LAST_COLUMN . '1')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 13, 'bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '0B45F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A2:' . self::LAST_COLUMN . '2')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '05B35B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '111827']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(34);
        $sheet->freezePane('A3');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaFilaDatos = self::FIRST_DATA_ROW + $this->filas->count() - 1;
                $filaFinal = $this->filas->isEmpty() ? self::FIRST_DATA_ROW : $ultimaFilaDatos + 1;

                $sheet->setAutoFilter('A2:' . self::LAST_COLUMN . $filaFinal);

                if ($this->filas->isEmpty()) {
                    $sheet->setCellValue('A3', 'SIN DEPOSITOS IMSS CAPTURADOS PARA ESTE PERIODO');
                    $sheet->mergeCells('A3:' . self::LAST_COLUMN . '3');
                    $sheet->getStyle('A3:' . self::LAST_COLUMN . '3')->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => ['argb' => '64748B']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F8FAFC']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    return;
                }

                foreach ($this->filas as $indice => $nomina) {
                    $fila = self::FIRST_DATA_ROW + $indice;
                    $color = $this->colorBanco($nomina->empleado?->banco);

                    $sheet->getStyle("A{$fila}:" . self::LAST_COLUMN . "{$fila}")->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => '111827']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '111827']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }

                $filaTotales = $ultimaFilaDatos + 1;
                $sheet->setCellValue("A{$filaTotales}", 'TOTALES');
                $sheet->mergeCells("A{$filaTotales}:C{$filaTotales}");

                foreach (['D', 'E', 'F', 'G'] as $columna) {
                    $sheet->setCellValue("{$columna}{$filaTotales}", "=SUM({$columna}" . self::FIRST_DATA_ROW . ":{$columna}{$ultimaFilaDatos})");
                }

                $sheet->getStyle("A{$filaTotales}:" . self::LAST_COLUMN . "{$filaTotales}")->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => '065F46']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'DCFCE7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '065F46']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => '065F46']],
                    ],
                ]);
                $sheet->getStyle("A{$filaTotales}:C{$filaTotales}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    private function colorBanco(?string $banco): string
    {
        $banco = strtoupper(trim((string) $banco));

        if (str_contains($banco, 'SANTANDER') || str_contains($banco, 'BANORTE') || str_contains($banco, 'BANAMEX')) {
            return 'FCA5A5';
        }

        if (str_contains($banco, 'AZTECA')) {
            return 'DCFCE7';
        }

        if (str_contains($banco, 'BBVA') || str_contains($banco, 'MERCADO')) {
            return 'BFDBFE';
        }

        if (str_contains($banco, 'COPPEL')) {
            return 'FEF08A';
        }

        if (str_contains($banco, 'HSBC')) {
            return 'E2E8F0';
        }

        if ($banco === 'NU' || str_contains($banco, 'NU ')) {
            return 'E9D5FF';
        }

        return 'F8FAFC';
    }
}
