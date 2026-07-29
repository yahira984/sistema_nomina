<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    use HasFactory;

    // AL DEJARLO VACÍO, PERMITIMOS QUE SÍ SE GUARDEN LAS MATEMÁTICAS EN LA BASE DE DATOS
    protected $guarded = [];

    protected $casts = [
        'calculation_snapshot' => 'array',
        'pagado' => 'boolean',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
