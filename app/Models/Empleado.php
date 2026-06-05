<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    // Aquí está la clave, le agregamos el numero_empleado para que deje guardarlo
    protected $guarded = [];
}