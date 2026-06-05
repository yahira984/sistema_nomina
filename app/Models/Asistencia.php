<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    // Con esta línea vacía le decimos a Laravel: "Deja pasar TODO, no bloquees ningún campo"
    protected $guarded = [];

    // Relación: Una asistencia le pertenece a un Empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}