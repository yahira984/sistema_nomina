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
        'dias_faltas_totales',
        'dias_laborados'
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function getAntiguedadAniosAttribute()
    {
        if (!$this->fecha_ingreso) return 0;
        $inicio = Carbon::parse($this->fecha_ingreso)->startOfDay();
        $fin = $this->fecha_baja ? Carbon::parse($this->fecha_baja)->startOfDay() : Carbon::now()->startOfDay();

        if ($fin->lt($inicio)) {
            return 0;
        }

        return (int) floor($inicio->diffInYears($fin));
    }

    public function getDiasVacacionesTotalesAttribute()
    {
        $anios = $this->antiguedad_anios;
        
        if ($anios < 1) return 0;
        if ($anios <= 5) return 10 + ($anios * 2);

        return 20 + ((int) ceil(($anios - 5) / 5) * 2);
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

    public function getDiasLaboradosAttribute($value)
    {
        if ((int) $value > 0) {
            return (int) $value;
        }

        if (!$this->fecha_ingreso || !$this->fecha_baja) {
            return 0;
        }

        $inicio = Carbon::parse($this->fecha_ingreso)->startOfDay();
        $fin = Carbon::parse($this->fecha_baja)->startOfDay();

        if ($fin->lt($inicio)) {
            return 0;
        }

        return (int) $inicio->diffInDays($fin) + 1;
    }
}
