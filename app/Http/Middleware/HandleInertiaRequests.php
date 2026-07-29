<?php

namespace App\Http\Middleware;

use App\Support\SecurityPermissions;
use App\Models\IntegrationFailure;
use App\Models\SystemOperation;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $permissions = $user?->effectivePermissions() ?? [];
        $preferences = $user && Schema::hasTable('user_preferences')
            ? UserPreference::firstOrCreate(['user_id' => $user->id])
            : null;

        return [
            ...parent::share($request),
            'appVersion' => config('app.version'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_label' => $user->roleLabel(),
                    'is_admin' => $user->isAdmin(),
                    'is_recovery_admin' => $user->isRecoveryAdmin(),
                    'approved_at' => $user->approved_at,
                    'disabled_at' => $user->disabled_at,
                    'preferences' => $preferences ? [
                        'theme' => $preferences->theme,
                        'density' => $preferences->density,
                        'sidebar_collapsed' => (bool) $preferences->sidebar_collapsed,
                        'saved_filters' => $preferences->saved_filters ?? [],
                    ] : [
                        'theme' => 'system',
                        'density' => 'comfortable',
                        'sidebar_collapsed' => false,
                        'saved_filters' => [],
                    ],
                ] : null,
                'permissions' => $permissions,
                'can' => collect(SecurityPermissions::allKeys())
                    ->mapWithKeys(fn (string $permission) => [$permission => in_array($permission, $permissions, true)])
                    ->all(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'operation_id' => fn () => $request->session()->get('operation_id'),
            ],
            'systemContext' => [
                'selected_period' => $request->input('fecha_corte') ?: $request->input('fecha'),
                'notifications' => fn () => $user && Schema::hasTable('system_operations')
                    ? [
                        'failed_operations' => SystemOperation::where('user_id', $user->id)
                            ->where('status', 'failed')
                            ->where('updated_at', '>=', now()->subDay())
                            ->count(),
                        'integration_failures' => $user->isAdmin() && Schema::hasTable('integration_failures')
                            ? IntegrationFailure::where('status', '!=', 'resolved')->count()
                            : 0,
                    ]
                    : ['failed_operations' => 0, 'integration_failures' => 0],
            ],
        ];
    }
}
