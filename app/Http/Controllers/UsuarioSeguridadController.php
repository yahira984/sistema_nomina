<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\SecurityPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioSeguridadController extends Controller
{
    public function index(): Response
    {
        $actor = request()->user();

        return Inertia::render('Seguridad/Usuarios', [
            'users' => User::query()
                ->orderByRaw('approved_at IS NULL DESC')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (User $user) => $this->serializeUser($user, $actor))
                ->values(),
            'roles' => collect(SecurityPermissions::roles())
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
                ->values(),
            'permissions' => SecurityPermissions::groupedPermissions(),
            'roleDefaults' => collect(array_keys(SecurityPermissions::roles()))
                ->mapWithKeys(fn (string $role) => [$role => SecurityPermissions::defaultsForRole($role)])
                ->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(SecurityPermissions::roles()))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(SecurityPermissions::allKeys())],
            'approved' => ['required', 'boolean'],
            'disabled' => ['required', 'boolean'],
        ]);

        $actor = $request->user();

        if ($user->isRecoveryAdmin() && (
            $validated['disabled']
            || !$validated['approved']
            || $validated['role'] !== SecurityPermissions::ADMIN
        )) {
            return back()->withErrors([
                'user' => 'La cuenta de recuperacion no se puede deshabilitar ni quitar de administrador.',
            ]);
        }

        if ($actor->id === $user->id && ($validated['disabled'] || !$validated['approved'] || $validated['role'] !== SecurityPermissions::ADMIN)) {
            return back()->withErrors([
                'user' => 'No puedes quitarte tu propio acceso administrativo.',
            ]);
        }

        if ($this->wouldRemoveLastAdmin($user, $validated)) {
            return back()->withErrors([
                'user' => 'Debe quedar al menos un administrador aprobado y activo.',
            ]);
        }

        $oldValues = [
            'role' => $user->role,
            'permissions' => $user->permissions,
            'approved_at' => $user->approved_at,
            'approved_by' => $user->approved_by,
            'disabled_at' => $user->disabled_at,
        ];

        $approved = (bool) $validated['approved'];
        $disabled = (bool) $validated['disabled'];

        $user->forceFill([
            'role' => $user->isRecoveryAdmin() ? SecurityPermissions::ADMIN : $validated['role'],
            'permissions' => ($user->isRecoveryAdmin() || $validated['role'] === SecurityPermissions::ADMIN)
                ? []
                : array_values(array_unique($validated['permissions'] ?? [])),
            'approved_at' => ($user->isRecoveryAdmin() || $approved) ? ($user->approved_at ?: now()) : null,
            'approved_by' => ($user->isRecoveryAdmin() || $approved) ? ($user->approved_by ?: $actor->id) : null,
            'disabled_at' => $user->isRecoveryAdmin() ? null : ($disabled ? ($user->disabled_at ?: now()) : null),
        ])->saveQuietly();

        AuditLog::record('security.user_permissions_updated', $user, [
            'description' => "Permisos actualizados para usuario #{$user->id}.",
            'old_values' => $oldValues,
            'new_values' => [
                'role' => $user->role,
                'permissions' => $user->permissions,
                'approved_at' => $user->approved_at,
                'approved_by' => $user->approved_by,
                'disabled_at' => $user->disabled_at,
            ],
        ]);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Solo un administrador puede borrar usuarios.');

        if ($user->isRecoveryAdmin()) {
            return back()->withErrors([
                'user' => 'La cuenta de recuperacion no se puede borrar.',
            ]);
        }

        if ($request->user()->id === $user->id) {
            return back()->withErrors([
                'user' => 'No puedes borrar tu propia cuenta desde aqui.',
            ]);
        }

        if ($user->isAdmin() && $user->canAccessSystem() && $this->activeAdminsExcept($user) === 0) {
            return back()->withErrors([
                'user' => 'No puedes borrar el ultimo administrador activo.',
            ]);
        }

        $oldValues = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'approved_at' => $user->approved_at,
            'disabled_at' => $user->disabled_at,
        ];

        User::withoutEvents(function () use ($user) {
            $user->delete();
        });

        AuditLog::record('security.user_deleted', null, [
            'description' => "Usuario #{$oldValues['id']} eliminado.",
            'auditable_type' => User::class,
            'auditable_id' => $oldValues['id'],
            'old_values' => $oldValues,
        ]);

        return back()->with('success', 'Usuario borrado correctamente.');
    }

    private function wouldRemoveLastAdmin(User $target, array $validated): bool
    {
        $keepsAdmin = $validated['role'] === SecurityPermissions::ADMIN
            && (bool) $validated['approved']
            && !(bool) $validated['disabled'];

        if ($keepsAdmin) {
            return false;
        }

        $activeAdmins = User::query()
            ->where('role', SecurityPermissions::ADMIN)
            ->whereNotNull('approved_at')
            ->whereNull('disabled_at')
            ->whereKeyNot($target->id)
            ->count();

        return $target->isAdmin() && $target->canAccessSystem() && $activeAdmins === 0;
    }

    private function activeAdminsExcept(User $target): int
    {
        return User::query()
            ->where('role', SecurityPermissions::ADMIN)
            ->whereNotNull('approved_at')
            ->whereNull('disabled_at')
            ->whereKeyNot($target->id)
            ->count();
    }

    private function serializeUser(User $user, User $viewer): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->visibleEmailFor($viewer),
            'display_email' => $user->visibleEmailFor($viewer) ?? $user->maskedEmail(),
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'is_recovery_admin' => $user->isRecoveryAdmin(),
            'permissions' => $user->effectivePermissions(),
            'custom_permissions' => $user->permissions ?? [],
            'approved_at' => $user->approved_at?->toDateTimeString(),
            'disabled_at' => $user->disabled_at?->toDateTimeString(),
            'created_at' => $user->created_at?->toDateTimeString(),
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
            'last_login_ip' => $user->last_login_ip,
            'status' => $user->isDisabled()
                ? 'disabled'
                : ($user->isApproved() ? 'approved' : 'pending'),
        ];
    }
}
