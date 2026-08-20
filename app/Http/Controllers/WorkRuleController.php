<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\LaborCalendarDay;
use App\Models\WorkRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WorkRuleController extends Controller
{
    public function index(Request $request)
    {
        $year = max(2020, min(2100, (int) $request->input('year', now()->year)));

        return Inertia::render('Sistema/ReglasLaborales', [
            'rules' => WorkRule::with('empleado:id,numero_empleado,nombre_completo')
                ->orderByDesc('priority')
                ->orderBy('name')
                ->get(),
            'calendar' => LaborCalendarDay::with('empleado:id,numero_empleado,nombre_completo')
                ->whereYear('date', $year)
                ->orderBy('date')
                ->get(),
            'employees' => Empleado::where('estatus', true)
                ->orderBy('nombre_completo')
                ->get(['id', 'numero_empleado', 'nombre_completo', 'puesto']),
            'positions' => Empleado::query()
                ->whereNotNull('puesto')
                ->where('puesto', '!=', '')
                ->distinct()
                ->orderBy('puesto')
                ->pluck('puesto'),
            'year' => $year,
        ]);
    }

    public function store(Request $request)
    {
        WorkRule::create($this->validatedRule($request));

        return back()->with('success', 'Regla laboral creada.');
    }

    public function update(Request $request, WorkRule $workRule)
    {
        $workRule->update($this->validatedRule($request));

        return back()->with('success', 'Regla laboral actualizada.');
    }

    public function destroy(WorkRule $workRule)
    {
        $workRule->delete();

        return back()->with('success', 'Regla laboral eliminada.');
    }

    public function storeCalendarDay(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'kind' => ['required', 'in:working,non_working'],
            'scope_type' => ['required', 'in:global,position,employee'],
            'empleado_id' => ['nullable', 'required_if:scope_type,employee', 'exists:empleados,id'],
            'position' => ['nullable', 'required_if:scope_type,position', 'string', 'max:255'],
            'shift' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);
        $data['scope_key'] = match ($data['scope_type']) {
            'employee' => 'employee:' . ($data['empleado_id'] ?? ''),
            'position' => 'position:' . Str::upper(Str::ascii(trim((string) ($data['position'] ?? '')))),
            default => 'global',
        };
        $data['active'] = $request->boolean('active', true);

        LaborCalendarDay::updateOrCreate(
            [
                'date' => $data['date'],
                'scope_type' => $data['scope_type'],
                'scope_key' => $data['scope_key'],
            ],
            $data
        );

        return back()->with('success', 'Día del calendario guardado.');
    }

    public function destroyCalendarDay(LaborCalendarDay $laborCalendarDay)
    {
        $laborCalendarDay->delete();

        return back()->with('success', 'Día del calendario eliminado.');
    }

    private function validatedRule(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope_type' => ['required', 'in:global,employee,employee_number,position,position_contains'],
            'scope_value' => [
                'nullable',
                'required_if:scope_type,employee_number,position,position_contains',
                'string',
                'max:255',
            ],
            'empleado_id' => ['nullable', 'required_if:scope_type,employee', 'exists:empleados,id'],
            'turno_24x24' => ['nullable', 'boolean'],
            'sin_horas_extra' => ['nullable', 'boolean'],
            'sin_retardos' => ['nullable', 'boolean'],
            'pago_por_hora_topado' => ['nullable', 'boolean'],
            'tope_horas_semanales' => ['nullable', 'numeric', 'min:0', 'max:168'],
            'hora_entrada' => ['nullable', 'date_format:H:i'],
            'hora_salida' => ['nullable', 'date_format:H:i'],
            'hora_salida_jueves' => ['nullable', 'date_format:H:i'],
            'dias_laborales' => ['nullable', 'array'],
            'dias_laborales.*' => ['integer', 'between:1,7'],
            'fecha_referencia_turno' => ['nullable', 'date'],
            'priority' => ['nullable', 'integer', 'between:1,1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        foreach (['turno_24x24', 'sin_horas_extra', 'sin_retardos', 'pago_por_hora_topado'] as $field) {
            $value = $request->input($field);
            $data[$field] = $value === null ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (($data['scope_type'] ?? null) === 'employee') {
            $data['scope_value'] = null;
        } else {
            $data['empleado_id'] = null;
        }

        $data['priority'] = (int) ($data['priority'] ?? 100);
        $data['active'] = $request->boolean('active', true);

        return $data;
    }
}
