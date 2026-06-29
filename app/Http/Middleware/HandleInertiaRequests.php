<?php

namespace App\Http\Middleware;

use App\Support\SecurityPermissions;
use Illuminate\Http\Request;
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

        return [
            ...parent::share($request),
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
                ] : null,
                'permissions' => $permissions,
                'can' => collect(SecurityPermissions::allKeys())
                    ->mapWithKeys(fn (string $permission) => [$permission => in_array($permission, $permissions, true)])
                    ->all(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
