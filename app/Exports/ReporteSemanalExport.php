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
    private const LAST_COLUMN = 'X';

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
            ['REPORTE GENERAL DE NOMINA PACHUCA'],
            ['SEMANA ' . $this->semana . ' | PERIODO ' . $this->textoPeriodo()],
            ['GENERADO: ' . now()->format('d/m/Y H:i')],
            [''],
            [
                'No.',
                'INST.',
                'NOMBRE',
                'TIPO',
                'SUELDO BASE',
                '$/DIARIO',
                '$/HR',
                'HRS EXTRA',
                'HRS PAG.',
                'HRS DESC.',
                'SALDO HRS',
                'FALTAS',
                'FALTAS PAG.',
                'DIAS VAC.',
                'PAGO VAC.',
                'COMPENSACION',
                'ADEUDO',
                'IMSS',
                'ISR',
                'INFONAVIT',
                'DESC.',
                'PERCEPCIONES',
                'DEDUCCIONES',
                'NETO',
            ],
        ];
    }

    public function map($nomina): array
    {
        $empleado = $nomina->empleado;
        $esEstudiante = (bool) ($empleado?->es_estudiante ?? false);
        $sueldoSemanal = (float) ($empleado?->sueldo_semanal ?? 0);
        $sueldoHora = (float) ($empleado?->sueldo_por_hora ?? 0);
        $sueldoBase = $esEstudiante ? $sueldoHora : $sueldoSemanal;
        $sueldoDiario = $esEstudiante ? 0 : ($sueldoSemanal > 0 ? $sueldoSemanal / 7 : 0);
        $tarifaHora = $esEstudiante ? $sueldoHora : ($sueldoSemanal > 0 ? $sueldoSemanal / 56 : 0);
        $saldoHoras = $this->saldoHorasHastaNomina($nomina);
        $diasVacaciones = (float) ($nomina->dias_vacaciones_pagadas ?? 0);
        $pagoVacaciones = $esEstudiante ? 0 : $sueldoDiario * 1.25 * $diasVacaciones;

        return [
            $empleado?->numero_empleado ?? $empleado?->numero_empleado_baja ?? 'S/N',
            'PACHUCA',
            strtoupper($empleado?->nombre_completo ?? 'SIN EMPLEADO'),
            $esEstudiante ? 'ESTUDIANTE' : 'EMPLEADO',
            $sueldoBase,
            $sueldoDiario,
            $tarifaHora,
            (float) $nomina->horas_extra,
            (float) ($nomina->horas_extra_pagadas ?? $nomina->horas_extra),
            (float) ($nomina->horas_adeudo_descontadas ?? 0),
            $saldoHoras,
            $this->faltasDescontadas($nomina),
            (int) ($nomina->faltas_pagadas ?? 0),
            $diasVacaciones,
            $pagoVacaciones,
            (float) ($nomina->prestamo_otorgado ?? 0),
            (float) ($nomina->prestamo_descuento ?? 0),
            (float) ($empleado?->descuento_imss ?? 0),
            (float) ($empleado?->descuento_isr ?? 0),
            (float) ($empleado?->descuento_infonavit ?? 0),
            (float) ($nomina->deduccion_manual ?? 0),
            (float) $nomina->total_percepciones,
            (float) $nomina->total_deducciones,
            (float) $nomina->pago_neto,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '"$"#,##0.00',
            'F' => '"$"#,##0.00',
            'G' => '"$"#,##0.00',
            'H' => '0.00',
            'I' => '0.00',
            'J' => '0.00',
            'K' => '0.00',
            'N' => '0.00',
            'O' => '"$"#,##0.00',
            'P' => '"$"#,##0.00',
            'Q' => '"$"#,##0.00',
            'R' => '"$"#,##0.00',
            'S' => '"$"#,##0.00',
            'T' => '"$"#,##0.00',
            'U' => '"$"#,##0.00',
            'V' => '"$"#,##0.00',
            'W' => '"$"#,##0.00',
            'X' => '"$"#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $filaCabecera = 6;
        $primeraFilaDatos = 7;
        $ultimaFilaDatos = $primeraFilaDatos + $this->totalFilas - 1;
        $filaFinal = $this->totalFilas > 0 ? $ultimaFilaDatos + 1 : $primeraFilaDatos;

        foreach (range(1, 4) as $fila) {
            $sheet->mergeCells("A{$fila}:" . self::LAST_COLUMN . "{$fila}");
        }

        $sheet->getStyle('A1:' . self::LAST_COLUMN . '1')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle('A2:' . self::LAST_COLUMN . '4')->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$filaCabecera}:" . self::LAST_COLUMN . "{$filaCabecera}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '1E293B']]],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension($filaCabecera)->setRowHeight(32);
        $sheet->freezePane('A7');
        $sheet->setAutoFilter("A{$filaCabecera}:" . self::LAST_COLUMN . "{$filaFinal}");

        if ($this->totalFilas === 0) {
            $sheet->setCellValue("A{$primeraFilaDatos}", 'SIN REGISTROS PARA ESTE PERIODO');
            $sheet->mergeCells("A{$primeraFilaDatos}:" . self::LAST_COLUMN . "{$primeraFilaDatos}");
            $sheet->getStyle("A{$primeraFilaDatos}:" . self::LAST_COLUMN . "{$primeraFilaDatos}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => ['argb' => '64748B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'F8FAFC']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'CBD5E1']]],
            ]);

            return [];
        }

        $rangoCuerpo = "A{$primeraFilaDatos}:" . self::LAST_COLUMN . "{$ultimaFilaDatos}";
        $sheet->getStyle($rangoCuerpo)->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9, 'color' => ['argb' => '0F172A']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A{$primeraFilaDatos}:B{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$primeraFilaDatos}:N{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("O{$primeraFilaDatos}:" . self::LAST_COLUMN . "{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $filaTotales = $ultimaFilaDatos + 1;
        $sheet->setCellValue("A{$filaTotales}", 'TOTALES');
        $sheet->mergeCells("A{$filaTotales}:D{$filaTotales}");

        foreach (['H', 'I', 'J', 'K', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'] as $columna) {
            $sheet->setCellValue("{$columna}{$filaTotales}", "=SUM({$columna}{$primeraFilaDatos}:{$columna}{$ultimaFilaDatos})");
        }

        $sheet->getStyle("A{$filaTotales}:" . self::LAST_COLUMN . "{$filaTotales}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10, 'bold' => true, 'color' => ['argb' => '065F46']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'ECFDF5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '065F46']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => '065F46']],
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

    private function saldoHorasHastaNomina(Nomina $nomina): float
    {
        $generadas = Nomina::where('empleado_id', $nomina->empleado_id)
            ->whereDate('fecha_inicio', '<=', $nomina->fecha_inicio)
            ->sum('horas_adeudo_generadas');
        $descontadas = Nomina::where('empleado_id', $nomina->empleado_id)
            ->whereDate('fecha_inicio', '<=', $nomina->fecha_inicio)
            ->sum('horas_adeudo_descontadas');

        return max(0, round((float) $generadas - (float) $descontadas, 2));
    }

    private function faltasDescontadas(Nomina $nomina): int
    {
        if (!$this->inicioSemana || !$this->finSemana || !$nomina->empleado) {
            return 0;
        }

        $faltas = $nomina->empleado->asistencias()
            ->whereBetween('fecha', [$this->inicioSemana->format('Y-m-d'), $this->finSemana->format('Y-m-d')])
            ->where('tipo_asistencia', 'Falta')
            ->count();

        return max(0, $faltas - (int) ($nomina->faltas_pagadas ?? 0));
    }
}
