<?php

namespace App\Support;

class SecurityPermissions
{
    public const ADMIN = 'admin';
    public const MANAGER = 'gerente';
    public const CAPTURIST = 'capturista';
    public const VIEWER = 'consulta';

    public static function roles(): array
    {
        return [
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Gerente / RH',
            self::CAPTURIST => 'Capturista',
            self::VIEWER => 'Solo consulta',
        ];
    }

    public static function permissions(): array
    {
        return [
            'dashboard.view' => [
                'label' => 'Ver panel',
                'group' => 'General',
            ],
            'empleados.view' => [
                'label' => 'Ver empleados',
                'group' => 'Empleados',
            ],
            'empleados.manage' => [
                'label' => 'Alta, baja y edicion de empleados',
                'group' => 'Empleados',
            ],
            'asistencias.view' => [
                'label' => 'Ver asistencias',
                'group' => 'Asistencias',
            ],
            'asistencias.manage' => [
                'label' => 'Capturar y editar asistencias',
                'group' => 'Asistencias',
            ],
            'asistencias.import' => [
                'label' => 'Importar reloj checador',
                'group' => 'Asistencias',
            ],
            'asistencias.export' => [
                'label' => 'Exportar asistencias',
                'group' => 'Asistencias',
            ],
            'nominas.view' => [
                'label' => 'Ver nominas',
                'group' => 'Nominas',
            ],
            'nominas.manage' => [
                'label' => 'Generar recibos y editar ajustes',
                'group' => 'Nominas',
            ],
            'nominas.pay' => [
                'label' => 'Marcar nominas pagadas',
                'group' => 'Nominas',
            ],
            'nominas.export' => [
                'label' => 'Exportar recibos/reportes',
                'group' => 'Nominas',
            ],
            'sistema.dias_festivos' => [
                'label' => 'Administrar dias festivos',
                'group' => 'Sistema',
            ],
            'sistema.backups' => [
                'label' => 'Respaldar/restaurar base de datos',
                'group' => 'Sistema',
            ],
            'sistema.users' => [
                'label' => 'Aprobar usuarios y permisos',
                'group' => 'Seguridad',
            ],
            'sistema.audit' => [
                'label' => 'Ver bitacora de auditoria',
                'group' => 'Seguridad',
            ],
        ];
    }

    public static function groupedPermissions(): array
    {
        return collect(self::permissions())
            ->map(fn (array $permission, string $key) => [
                'key' => $key,
                'label' => $permission['label'],
                'group' => $permission['group'],
            ])
            ->groupBy('group')
            ->map(fn ($items, string $group) => [
                'group' => $group,
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();
    }

    public static function allKeys(): array
    {
        return array_keys(self::permissions());
    }

    public static function defaultsForRole(string $role): array
    {
        return match ($role) {
            self::ADMIN => self::allKeys(),
            self::MANAGER => [
                'dashboard.view',
                'empleados.view',
                'empleados.manage',
                'asistencias.view',
                'asistencias.manage',
                'asistencias.import',
                'asistencias.export',
                'nominas.view',
                'nominas.manage',
                'nominas.pay',
                'nominas.export',
                'sistema.dias_festivos',
            ],
            self::CAPTURIST => [
                'dashboard.view',
                'empleados.view',
                'asistencias.view',
                'asistencias.manage',
                'asistencias.import',
                'asistencias.export',
            ],
            default => [
                'dashboard.view',
                'empleados.view',
                'asistencias.view',
                'nominas.view',
            ],
        };
    }
}
