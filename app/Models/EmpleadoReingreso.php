<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoReingreso extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fecha_reingreso' => 'date:Y-m-d',
        'fecha_baja_anterior' => 'date:Y-m-d',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
