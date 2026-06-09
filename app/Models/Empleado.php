<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Empleado extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Agregamos las faltas totales al appends
    protected $appends = [
        'antiguedad_anios', 
        'dias_vacaciones_totales', 
        'dias_vacaciones_tomados', 
        'dias_vacaciones_restantes',
        'dias_faltas_totales'
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function getAntiguedadAniosAttribute()
    {
        if (!$this->fecha_ingreso) return 0;
        $fin = $this->fecha_baja ? Carbon::parse($this->fecha_baja) : Carbon::now();

        return Carbon::parse($this->fecha_ingreso)->diffInYears($fin);
    }

    public function getDiasVacacionesTotalesAttribute()
    {
        $anios = $this->antiguedad_anios;
        
        if ($anios < 1) return 0;
        if ($anios == 1) return 12;
        if ($anios == 2) return 14;
        if ($anios == 3) return 16;
        if ($anios == 4) return 18;
        if ($anios == 5) return 20;
        if ($anios >= 6 && $anios <= 10) return 22;
        if ($anios >= 11 && $anios <= 15) return 24;
        if ($anios >= 16 && $anios <= 20) return 26;
        if ($anios >= 21 && $anios <= 25) return 28;
        if ($anios >= 26 && $anios <= 30) return 30;
        if ($anios >= 31 && $anios <= 35) return 32;
        
        return 32;
    }

    public function getDiasVacacionesTomadosAttribute()
    {
        return $this->asistencias()
            ->where('tipo_asistencia', 'Vacaciones')
            ->count(); 
    }

    public function getDiasVacacionesRestantesAttribute()
    {
        // Así ya lee el ajuste y permite mostrar los saldos negativos del 2025
        return ($this->dias_vacaciones_totales - $this->dias_vacaciones_tomados) + $this->ajuste_vacaciones;
    }

    public function getDiasFaltasTotalesAttribute()
    {
        return $this->asistencias()
            ->where('tipo_asistencia', 'Falta')
            ->count(); 
    }
}
