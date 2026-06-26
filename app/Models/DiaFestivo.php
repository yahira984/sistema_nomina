<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaFestivo extends Model
{
    use HasFactory;

    protected $table = 'dias_festivos';

    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
        'es_oficial' => 'boolean',
        'activo' => 'boolean',
    ];

    protected $appends = [
        'fecha_formateada',
        'dia_semana',
        'dias_restantes',
    ];

    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha
            ? Carbon::parse($this->fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
            : '';
    }

    public function getDiaSemanaAttribute(): string
    {
        return $this->fecha
            ? Carbon::parse($this->fecha)->locale('es')->isoFormat('dddd')
            : '';
    }

    public function getDiasRestantesAttribute(): int
    {
        if (!$this->fecha) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->fecha)->startOfDay(), false);
    }
}
