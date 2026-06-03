<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'numero_semana',
        'fecha_inicio',
        'fecha_fin',
        'horas_normales',
        'horas_extra',
        'total_percepciones',
        'total_deducciones',
        'pago_neto',
        'pagado'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}