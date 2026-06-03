<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'horas_trabajadas'
    ];

    // Relación: Una asistencia le pertenece a un Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}