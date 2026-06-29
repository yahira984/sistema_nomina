<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $viewer = $request->user();

        $filters = $this->validatedFilters($request);

        $logs = $this->applyFilters(AuditLog::query()->with('user:id,name,email'), $filters)
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log) => $this->serializeLog($log, $viewer));

        return Inertia::render('Seguridad/Auditoria', [
            'logs' => $logs,
            'filters' => $filters,
            'canDeleteAudit' => $viewer->isAdmin(),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->visibleEmailFor($viewer),
                    'display_email' => $user->visibleEmailFor($viewer) ?? $user->maskedEmail(),
                ]),
            'events' => AuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->map(fn (string $event) => [
                    'key' => $event,
                    'label' => $this->eventLabel($event),
                ]),
        ]);
    }

    public function destroy(Request $request, AuditLog $auditLog): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo un administrador puede borrar registros de auditoria.');

        $deleted = [
            'id' => $auditLog->id,
            'event' => $auditLog->event,
            'description' => $auditLog->description,
            'created_at' => $auditLog->created_at?->toDateTimeString(),
        ];

        $auditLog->delete();

        AuditLog::record('audit_log.deleted', null, [
            'description' => "Registro de auditoria #{$deleted['id']} eliminado.",
            'metadata' => ['deleted_log' => $deleted],
        ]);

        return back()->with('success', 'Registro de auditoria borrado.');
    }

    public function purge(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo un administrador puede limpiar la auditoria.');

        $filters = $this->validatedFilters($request);
        $query = $this->applyFilters(AuditLog::query(), $filters);
        $count = (clone $query)->count();

        $query->delete();

        AuditLog::record('audit_log.purged', null, [
            'description' => "{$count} registro(s) de auditoria eliminados.",
            'metadata' => [
                'deleted_count' => $count,
                'filters' => $filters,
            ],
        ]);

        return back()->with('success', "{$count} registro(s) de auditoria borrado(s).");
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'string', 'max:120'],
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
    }

    private function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%");
                });
            });
    }

    private function serializeLog(AuditLog $log, User $viewer): array
    {
        $oldValues = $this->visiblePayload($log->old_values, $viewer);
        $newValues = $this->visiblePayload($log->new_values, $viewer);
        $metadata = $this->visiblePayload($log->metadata, $viewer);

        return [
            'id' => $log->id,
            'event' => $log->event,
            'action' => $this->eventLabel($log->event),
            'area' => $this->areaLabel($log),
            'summary' => $this->summary($log),
            'description' => $log->description,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->visibleEmailFor($viewer),
                'display_email' => $log->user->visibleEmailFor($viewer) ?? $log->user->maskedEmail(),
            ] : null,
            'auditable_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
            'auditable_label' => $this->auditableLabel($log),
            'auditable_id' => $log->auditable_id,
            'ip_address' => $log->ip_address,
            'method' => $log->method,
            'url' => $log->url,
            'route' => $log->metadata['route'] ?? null,
            'changes' => $this->humanChanges($oldValues, $newValues),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'created_at' => $log->created_at?->toDateTimeString(),
            'created_at_human' => $log->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function eventLabel(string $event): string
    {
        $labels = [
            'auth.login' => 'Inicio de sesion',
            'auth.logout' => 'Cierre de sesion',
            'auth.login_blocked' => 'Acceso bloqueado',
            'auth.session_blocked' => 'Sesion bloqueada',
            'auth.password_updated' => 'Cambio de contrasena',
            'auth.registration_pending' => 'Registro pendiente',
            'auth.first_admin_registered' => 'Primer administrador creado',
            'database.import_started' => 'Restauracion iniciada',
            'database.import_finished' => 'Restauracion finalizada',
            'security.user_permissions_updated' => 'Cambio de permisos',
            'security.user_deleted' => 'Usuario borrado',
            'audit_log.deleted' => 'Registro de auditoria borrado',
            'audit_log.purged' => 'Limpieza de auditoria',
            'http.request' => 'Accion del sistema',
        ];

        if (isset($labels[$event])) {
            return $labels[$event];
        }

        if (str_ends_with($event, '.created')) {
            return 'Registro creado';
        }

        if (str_ends_with($event, '.updated')) {
            return 'Registro actualizado';
        }

        if (str_ends_with($event, '.deleted')) {
            return 'Registro borrado';
        }

        return str_replace(['_', '.'], ' ', $event);
    }

    private function areaLabel(AuditLog $log): string
    {
        $event = $log->event;
        $route = (string) ($log->metadata['route'] ?? '');

        foreach ([
            'auth' => 'Acceso',
            'empleado' => 'Empleados',
            'asistencia' => 'Asistencias',
            'nomina' => 'Nominas',
            'dia_festivo' => 'Dias festivos',
            'database' => 'Base de datos',
            'security' => 'Seguridad',
            'audit_log' => 'Auditoria',
        ] as $prefix => $area) {
            if (str_starts_with($event, $prefix)) {
                return $area;
            }
        }

        if (str_starts_with($route, 'empleados.')) return 'Empleados';
        if (str_starts_with($route, 'asistencias.')) return 'Asistencias';
        if (str_starts_with($route, 'nominas.')) return 'Nominas';
        if (str_starts_with($route, 'base-datos.')) return 'Base de datos';
        if (str_starts_with($route, 'seguridad.')) return 'Seguridad';

        return 'Sistema';
    }

    private function auditableLabel(AuditLog $log): string
    {
        if (!$log->auditable_type) {
            return 'Sistema';
        }

        $label = [
            'User' => 'Usuario',
            'Empleado' => 'Empleado',
            'Asistencia' => 'Asistencia',
            'Nomina' => 'Nomina',
            'DiaFestivo' => 'Dia festivo',
        ][class_basename($log->auditable_type)] ?? class_basename($log->auditable_type);

        return $log->auditable_id ? "{$label} #{$log->auditable_id}" : $label;
    }

    private function summary(AuditLog $log): string
    {
        $actor = $log->user?->name ?: 'El sistema';
        $action = strtolower($this->eventLabel($log->event));
        $target = $this->auditableLabel($log);

        if ($target === 'Sistema') {
            return "{$actor}: {$action}.";
        }

        return "{$actor}: {$action} en {$target}.";
    }

    private function humanChanges(?array $oldValues, ?array $newValues): array
    {
        $keys = collect(array_keys($oldValues ?? []))
            ->merge(array_keys($newValues ?? []))
            ->unique()
            ->reject(fn (string $key) => in_array($key, ['id'], true))
            ->values();

        return $keys
            ->map(function (string $key) use ($oldValues, $newValues) {
                $before = $oldValues[$key] ?? null;
                $after = $newValues[$key] ?? null;

                if ($before === $after) {
                    return null;
                }

                return [
                    'field' => $this->fieldLabel($key),
                    'before' => $this->formatValue($before),
                    'after' => $this->formatValue($after),
                ];
            })
            ->filter()
            ->take(12)
            ->values()
            ->all();
    }

    private function fieldLabel(string $field): string
    {
        return [
            'name' => 'Nombre',
            'email' => 'Correo',
            'role' => 'Rol',
            'permissions' => 'Permisos',
            'approved_at' => 'Aprobado',
            'approved_by' => 'Aprobado por',
            'disabled_at' => 'Deshabilitado',
            'empleado_id' => 'Empleado',
            'nombre_completo' => 'Nombre completo',
            'numero_empleado' => 'Numero de empleado',
            'fecha' => 'Fecha',
            'fecha_inicio' => 'Fecha inicial',
            'fecha_fin' => 'Fecha final',
            'tipo_asistencia' => 'Tipo de asistencia',
            'hora_entrada' => 'Hora de entrada',
            'hora_salida' => 'Hora de salida',
            'pagado' => 'Pagado',
            'pago_neto' => 'Pago neto',
            'deposito_imss' => 'Deposito IMSS',
            'saldo_prestamo' => 'Saldo de prestamo',
            'estatus' => 'Estatus',
            'method' => 'Metodo',
            'route' => 'Ruta',
            'status' => 'Estado',
        ][$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Sin dato';
        }

        if (is_bool($value)) {
            return $value ? 'Si' : 'No';
        }

        if (is_array($value)) {
            return count($value) === 0 ? 'Ninguno' : implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : 'detalle', $value));
        }

        return (string) $value;
    }

    private function visiblePayload(?array $payload, User $viewer): ?array
    {
        if ($viewer->isAdmin() || $payload === null) {
            return $payload;
        }

        return $this->redactEmails($payload);
    }

    private function redactEmails(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $keyString = strtolower((string) $key);

            if (str_contains($keyString, 'email') || str_contains($keyString, 'correo')) {
                $payload[$key] = 'correo oculto';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->redactEmails($value);
            }
        }

        return $payload;
    }
}
