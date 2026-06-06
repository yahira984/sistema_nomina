<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReciboIndividualExport implements FromView, ShouldAutoSize
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function view(): View
    {
        // Pasamos los datos usando "with" para que Blade los reconozca como variables individuales
        return view('excel.recibo_individual', $this->datos);
    }
}