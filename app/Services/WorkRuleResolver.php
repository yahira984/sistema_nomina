<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\WorkRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WorkRuleResolver
{
    private static array $cache = [];
    private static ?\Illuminate\Support\Collection $activeRules = null;
    private static ?bool $rulesTableExists = null;

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
            'hora_salida_jueves' => '17:30:00',
            'dias_laborales' => [1, 2, 3, 4, 5],
            'fecha_referencia_turno' => null,
            'rule_ids' => [],
            'rule_names' => [],
        ];

        if (!(self::$rulesTableExists ??= Schema::hasTable('work_rules'))) {
            return self::$cache[$cacheKey] = $defaults;
        }

        $numero = self::employeeNumber($empleado);
        $puesto = self::normalize($empleado->puesto);

        $rules = self::activeRules()
            ->filter(fn (WorkRule $rule) => match ($rule->scope_type) {
                'global' => true,
                'employee' => (int) $rule->empleado_id === (int) $empleado->id,
                'employee_number' => $numero !== null && self::employeeNumberValue($rule->scope_value) === $numero,
                'position' => $puesto !== '' && self::normalize($rule->scope_value) === $puesto,
                'position_contains' => $puesto !== ''
                    && self::normalize($rule->scope_value) !== ''
                    && Str::contains($puesto, self::normalize($rule->scope_value)),
                default => false,
            })
            ->sortByDesc(fn (WorkRule $rule) => sprintf('%010d-%010d', $rule->priority, $rule->id))
            ->values();

        $resolved = $defaults;
        $fields = [
            'turno_24x24',
            'sin_horas_extra',
            'sin_retardos',
            'pago_por_hora_topado',
            'tope_horas_semanales',
            'hora_entrada',
            'hora_salida',
            'hora_salida_jueves',
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
        self::$activeRules = null;
        self::$rulesTableExists = null;
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

    private static function activeRules(): \Illuminate\Support\Collection
    {
        return self::$activeRules ??= WorkRule::query()
            ->where('active', true)
            ->get();
    }

    private static function employeeNumberValue($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return ltrim($value, '0') ?: '0';
    }
}
