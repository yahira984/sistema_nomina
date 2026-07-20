<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Support\DiasLaborados;
use App\Support\HorarioLaboralEmpleado;

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
        'fechas_faltas',
        'dias_laborados',
        'dias_laborados_anio_baja',
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function nominas()
    {
        return $this->hasMany(Nomina::class);
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
        $diasCapturados = (float) $this->asistencias()
            ->where('tipo_asistencia', 'Vacaciones')
            ->count();

        $diasPagadosEnNomina = (float) $this->nominas()
            ->where('pagado', true)
            ->sum('dias_vacaciones_pagadas');

        return round(max($diasCapturados, $diasPagadosEnNomina), 2);
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
            ->get(['fecha'])
            ->filter(fn ($asistencia) => HorarioLaboralEmpleado::esDiaLaboral($this, $asistencia->fecha))
            ->count(); 
    }

    public function getFechasFaltasAttribute()
    {
        return $this->asistencias()
            ->where('tipo_asistencia', 'Falta')
            ->orderBy('fecha', 'desc')
            ->get(['fecha'])
            ->filter(fn ($asistencia) => HorarioLaboralEmpleado::esDiaLaboral($this, $asistencia->fecha))
            ->map(fn ($asistencia) => Carbon::parse($asistencia->fecha)->format('Y-m-d'))
            ->values();
    }

    public function getDiasLaboradosAttribute($value)
    {
        if (!$this->fecha_ingreso || !$this->fecha_baja) {
            return (int) ($value ?? 0);
        }

        return DiasLaborados::contarSinDomingos($this->fecha_ingreso, $this->fecha_baja);
    }

    public function getDiasLaboradosAnioBajaAttribute($value)
    {
        if (!$this->fecha_ingreso || !$this->fecha_baja) {
            return (int) ($value ?? 0);
        }

        return DiasLaborados::contarAnioDeBaja($this->fecha_ingreso, $this->fecha_baja);
    }
}
