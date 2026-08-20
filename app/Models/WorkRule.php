<?php

namespace App\Models;

use App\Services\WorkRuleResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'turno_24x24' => 'boolean',
        'sin_horas_extra' => 'boolean',
        'sin_retardos' => 'boolean',
        'pago_por_hora_topado' => 'boolean',
        'tope_horas_semanales' => 'float',
        'dias_laborales' => 'array',
        'fecha_referencia_turno' => 'date:Y-m-d',
        'priority' => 'integer',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => WorkRuleResolver::forget());
        static::deleted(fn () => WorkRuleResolver::forget());
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
