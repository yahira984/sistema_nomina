<?php

namespace App\Exports;

use App\Models\Nomina;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteSemanalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected int $semana;
    protected ?Carbon $inicioSemana;
    protected ?Carbon $finSemana;
    protected int $totalFilas = 0;

    public function __construct(int $semana, ?Carbon $inicioSemana = null, ?Carbon $finSemana = null)
    {
        $this->semana = $semana;
        $this->inicioSemana = $inicioSemana;
        $this->finSemana = $finSemana;
    }

    public function collection()
    {
        $consulta = Nomina::with('empleado')->orderBy('empleado_id');

        if ($this->inicioSemana && $this->finSemana) {
            $consulta->whereDate('fecha_inicio', $this->inicioSemana->format('Y-m-d'))
                ->whereDate('fecha_fin', $this->finSemana->format('Y-m-d'));
        } else {
            $consulta->where('numero_semana', $this->semana);
        }

        $coleccion = $consulta->get();
        $this->totalFilas = $coleccion->count();

        return $coleccion;
    }

    public function headings(): array
    {
        return [
            ['PROMATEC / LUGARTH'],
            ['REPORTE GENERAL DE DISPERSION DE NOMINA'],
            ['SEMANA ' . $this->semana . ' | PERIODO ' . $this->textoPeriodo()],
            ['GENERADO: ' . now()->format('d/m/Y H:i')],
            [''],
            [
                'ID EMPLEADO',
                'NOMBRE DEL TRABAJADOR',
                'BANCO',
                'CUENTA / CLABE',
                'HRS NORM.',
                'HRS EXTRA',
                'PERCEPCIONES',
                'DEDUCCIONES',
                'NETO A PAGAR',
            ],
        ];
    }

    public function map($nomina): array
    {
        $empleado = $nomina->empleado;
        $formaPago = ($empleado?->forma_pago === 'Efectivo')
            ? 'EFECTIVO'
            : strtoupper($empleado?->banco ?? 'S/N');

        return [
            $empleado?->numero_empleado ?? 'S/N',
            strtoupper($empleado?->nombre_completo ?? 'SIN EMPLEADO'),
            $formaPago,
            $empleado?->numero_cuenta ?? 'S/N',
            (float) $nomina->horas_normales,
            (float) $nomina->horas_extra,
            (float) $nomina->total_percepciones,
            (float) $nomina->total_deducciones,
            (float) $nomina->pago_neto,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '0.00',
            'F' => '0.00',
            'G' => '"$"#,##0.00',
            'H' => '"$"#,##0.00',
            'I' => '"$"#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $filaCabecera = 6;
        $primeraFilaDatos = 7;
        $ultimaFilaDatos = $primeraFilaDatos + $this->totalFilas - 1;
        $filaFinal = $this->totalFilas > 0 ? $ultimaFilaDatos + 1 : $primeraFilaDatos;

        foreach (range(1, 4) as $fila) {
            $sheet->mergeCells("A{$fila}:I{$fila}");
        }

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 16,
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 12,
                'bold' => true,
                'color' => ['argb' => '0F766E'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A3:I4')->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['argb' => '475569'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$filaCabecera}:I{$filaCabecera}")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '1E293B'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension($filaCabecera)->setRowHeight(22);
        $sheet->freezePane('A7');
        $sheet->setAutoFilter("A{$filaCabecera}:I{$filaFinal}");

        if ($this->totalFilas === 0) {
            $sheet->setCellValue("A{$primeraFilaDatos}", 'SIN REGISTROS PARA ESTE PERIODO');
            $sheet->mergeCells("A{$primeraFilaDatos}:I{$primeraFilaDatos}");
            $sheet->getStyle("A{$primeraFilaDatos}:I{$primeraFilaDatos}")->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 11,
                    'bold' => true,
                    'color' => ['argb' => '64748B'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'F8FAFC'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'CBD5E1'],
                    ],
                ],
            ]);

            return [];
        }

        $rangoCuerpo = "A{$primeraFilaDatos}:I{$ultimaFilaDatos}";
        $sheet->getStyle($rangoCuerpo)->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'color' => ['argb' => '0F172A'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'],
                ],
            ],
        ]);

        $sheet->getStyle("A{$primeraFilaDatos}:A{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$primeraFilaDatos}:F{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$primeraFilaDatos}:I{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $filaTotales = $ultimaFilaDatos + 1;
        $sheet->setCellValue("A{$filaTotales}", 'TOTALES');
        $sheet->mergeCells("A{$filaTotales}:D{$filaTotales}");
        $sheet->setCellValue("E{$filaTotales}", "=SUM(E{$primeraFilaDatos}:E{$ultimaFilaDatos})");
        $sheet->setCellValue("F{$filaTotales}", "=SUM(F{$primeraFilaDatos}:F{$ultimaFilaDatos})");
        $sheet->setCellValue("G{$filaTotales}", "=SUM(G{$primeraFilaDatos}:G{$ultimaFilaDatos})");
        $sheet->setCellValue("H{$filaTotales}", "=SUM(H{$primeraFilaDatos}:H{$ultimaFilaDatos})");
        $sheet->setCellValue("I{$filaTotales}", "=SUM(I{$primeraFilaDatos}:I{$ultimaFilaDatos})");

        $sheet->getStyle("A{$filaTotales}:I{$filaTotales}")->applyFromArray([
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
                'color' => ['argb' => '065F46'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'ECFDF5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '065F46'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => '065F46'],
                ],
            ],
        ]);
        $sheet->getStyle("A{$filaTotales}:D{$filaTotales}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return [];
    }

    private function textoPeriodo(): string
    {
        if (!$this->inicioSemana || !$this->finSemana) {
            return 'SIN PERIODO DEFINIDO';
        }

        return $this->inicioSemana->format('d/m/Y') . ' AL ' . $this->finSemana->format('d/m/Y');
    }
}
