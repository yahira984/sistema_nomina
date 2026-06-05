<?php

namespace App\Exports;

use App\Models\Nomina;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteSemanalExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $semana;
    protected $totalFilas = 0;

    public function __construct($semana)
    {
        $this->semana = $semana;
    }

    public function collection()
    {
        $coleccion = Nomina::with('empleado')->where('numero_semana', $this->semana)->get();
        // Guardamos cuántos empleados hay para saber exactamente dónde meter la fila de totales
        $this->totalFilas = $coleccion->count();
        return $coleccion;
    }

    public function headings(): array
    {
        return [
            ['PROMATEC / LUGARTH - REPORTE GENERAL DE DISPERSIÓN'],
            ['SEMANA NOMINAL: ' . $this->semana],
            [''], // Espacio en blanco institucional
            [
                'ID EMPLEADO',
                'NOMBRE DEL TRABAJADOR',
                'BANCO',
                'CUENTA / CLABE',
                'HRS NORM.',
                'HRS EXTRA',
                'PERCEPCIONES',
                'DEDUCCIONES',
                'NETO A PAGAR'
            ]
        ];
    }

    public function map($nomina): array
    {
        return [
            $nomina->empleado->numero_empleado ?? 'S/N',
            strtoupper($nomina->empleado->nombre_completo),
            strtoupper($nomina->empleado->banco ?? 'EFECTIVO'),
            $nomina->empleado->numero_cuenta ?? 'S/N',
            (float) $nomina->horas_normales,
            (float) $nomina->horas_extra,
            (float) $nomina->total_percepciones,
            (float) $nomina->total_deducciones,
            (float) $nomina->pago_neto,
        ];
    }

    // Definimos qué columnas son de dinero o números para que Excel las entienda nativamente
    public function columnFormats(): array
    {
        return [
            'E' => '0.00', // Horas normales
            'F' => '0.00', // Horas extra
            'G' => '"$"#,##0.00', // Moneda Percepciones
            'H' => '"$"#,##0.00', // Moneda Deducciones
            'I' => '"$"#,##0.00', // Moneda Neto
        ];
    }

    // Diseñamos toda la estructura visual y metemos fórmulas dinámicas
    public function styles(Worksheet $sheet)
    {
        // 1. Título principal elegante (Fila 1)
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setName('Arial')->setSize(14)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('0F172A'); // Slate 900 (Mismo que el sistema)
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Subtítulo de semana (Fila 2)
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setName('Arial')->setSize(11)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0F766E')); // Teal 700
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 3. Encabezados de la tabla (Fila 4)
        $filaCabecera = 4;
        $sheet->getStyle("A{$filaCabecera}:I{$filaCabecera}")->getFont()->setName('Arial')->setSize(10)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle("A{$filaCabecera}:I{$filaCabecera}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1E293B'); // Slate 800
        $sheet->getStyle("A{$filaCabecera}:I{$filaCabecera}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 4. Estilos para los registros de los empleados
        $primeraFilaDatos = 5;
        $ultimaFilaDatos = $primeraFilaDatos + $this->totalFilas - 1;

        $rangoCuerpo = "A{$primeraFilaDatos}:I{$ultimaFilaDatos}";
        $sheet->getStyle($rangoCuerpo)->getFont()->setName('Arial')->setSize(10);
        $sheet->getStyle("A{$primeraFilaDatos}:A{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar IDs
        $sheet->getStyle("C{$primeraFilaDatos}:D{$ultimaFilaDatos}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar Bancos y Cuentas

        // Bordes delgaditos para los registros
        $sheet->getStyle($rangoCuerpo)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');

        // 5. FILA DE TOTALES DINÁMICOS AL FINAL (Fórmula pura de Excel)
        $filaTotales = $ultimaFilaDatos + 1;

        $sheet->setCellValue("A{$filaTotales}", 'TOTALES');
        $sheet->mergeCells("A{$filaTotales}:D{$filaTotales}"); // Juntamos las celdas de texto
        
        // Ponemos las fórmulas nativas SUM de Excel para que cuente todo de golpe
        $sheet->setCellValue("E{$filaTotales}", "=SUM(E{$primeraFilaDatos}:E{$ultimaFilaDatos})");
        $sheet->setCellValue("F{$filaTotales}", "=SUM(F{$primeraFilaDatos}:F{$ultimaFilaDatos})");
        $sheet->setCellValue("G{$filaTotales}", "=SUM(G{$primeraFilaDatos}:G{$ultimaFilaDatos})");
        $sheet->setCellValue("H{$filaTotales}", "=SUM(H{$primeraFilaDatos}:H{$ultimaFilaDatos})");
        $sheet->setCellValue("I{$filaTotales}", "=SUM(I{$primeraFilaDatos}:I{$ultimaFilaDatos})");

        // Estilos para la fila de totales (Verde esmeralda suave institucional)
        $rangoTotales = "A{$filaTotales}:I{$filaTotales}";
        $sheet->getStyle($rangoTotales)->getFont()->setName('Arial')->setSize(11)->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('065F46'));
        $sheet->getStyle($rangoTotales)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('ECFDF5'); // Verde éxito sutil
        
        // Bordes superior e inferior dobles para los totales (estilo contable clásico)
        $sheet->getStyle($rangoTotales)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('065F46');
        $sheet->getStyle($rangoTotales)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setARGB('065F46');

        return [];
    }
}