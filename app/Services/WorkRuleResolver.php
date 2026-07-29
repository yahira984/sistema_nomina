<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\WorkRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WorkRuleResolver
{
    private static array $cache = [];

    public static function for(Empleado $empleado): array
    {
        $cacheKey = implode('|', [
            $empleado->id,
            $empleado->numero_empleado,
            $empleado->numero_empleado_baja,
            $empleado->puesto,
        ]);

        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $defaults = [
            'turno_24x24' => false,
            'sin_horas_extra' => false,
            'sin_retardos' => false,
            'pago_por_hora_topado' => false,
            'tope_horas_semanales' => 48.0,
            'hora_entrada' => '08:00:00',
            'hora_salida' => '17:30:00',
            'dias_laborales' => [1, 2, 3, 4, 5],
            'fecha_referencia_turno' => null,
            'rule_ids' => [],
            'rule_names' => [],
        ];

        if (!Schema::hasTable('work_rules')) {
            return self::$cache[$cacheKey] = $defaults;
        }

        $numero = self::employeeNumber($empleado);
        $puesto = self::normalize($empleado->puesto);

        $rules = WorkRule::query()
            ->where('active', true)
            ->where(function ($query) use ($empleado, $numero, $puesto) {
                $query->where('scope_type', 'global')
                    ->orWhere(fn ($query) => $query
                        ->where('scope_type', 'employee')
                        ->where('empleado_id', $empleado->id));

                if ($numero !== null) {
                    $query->orWhere(fn ($query) => $query
                        ->where('scope_type', 'employee_number')
                        ->where('scope_value', $numero));
                }

                if ($puesto !== '') {
                    $query->orWhere(fn ($query) => $query
                        ->where('scope_type', 'position')
                        ->whereRaw('UPPER(scope_value) = ?', [$puesto]))
                        ->orWhere('scope_type', 'position_contains');
                }
            })
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->filter(function (WorkRule $rule) use ($puesto) {
                if ($rule->scope_type !== 'position_contains') {
                    return true;
                }

                $needle = self::normalize($rule->scope_value);

                return $needle !== '' && Str::contains($puesto, $needle);
            });

        $resolved = $defaults;
        $fields = [
            'turno_24x24',
            'sin_horas_extra',
            'sin_retardos',
            'pago_por_hora_topado',
            'tope_horas_semanales',
            'hora_entrada',
            'hora_salida',
            'dias_laborales',
            'fecha_referencia_turno',
        ];
        $assigned = [];

        foreach ($rules as $rule) {
            $resolved['rule_ids'][] = $rule->id;
            $resolved['rule_names'][] = $rule->name;

            foreach ($fields as $field) {
                if (isset($assigned[$field]) || $rule->{$field} === null) {
                    continue;
                }

                $value = $rule->{$field};
                $resolved[$field] = $field === 'fecha_referencia_turno' && $value
                    ? $value->format('Y-m-d')
                    : $value;
                $assigned[$field] = true;
            }
        }

        return self::$cache[$cacheKey] = $resolved;
    }

    public static function forget(): void
    {
        self::$cache = [];
    }

    public static function employeeNumber(Empleado $empleado): ?string
    {
        $value = trim((string) ($empleado->numero_empleado ?? $empleado->numero_empleado_baja ?? ''));

        if ($value === '') {
            return null;
        }

        $normalized = ltrim($value, '0');

        return $normalized === '' ? '0' : $normalized;
    }

    private static function normalize($value): string
    {
        return Str::upper(Str::ascii(trim((string) $value)));
    }
}
