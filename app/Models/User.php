<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\SecurityPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'permissions',
    'approved_at',
    'approved_by',
    'disabled_at',
    'last_login_at',
    'last_login_ip',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'approved_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === SecurityPermissions::ADMIN;
    }

    public function isRecoveryAdmin(): bool
    {
        $recoveryEmail = (string) config('security.recovery_admin_email');

        return $recoveryEmail !== ''
            && Str::lower((string) $this->email) === Str::lower($recoveryEmail);
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function canAccessSystem(): bool
    {
        return $this->isApproved() && !$this->isDisabled();
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->canAccessSystem()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function effectivePermissions(): array
    {
        if ($this->isAdmin()) {
            return SecurityPermissions::allKeys();
        }

        $permissions = $this->permissions;

        if (!is_array($permissions) || count($permissions) === 0) {
            $permissions = SecurityPermissions::defaultsForRole($this->role ?: SecurityPermissions::VIEWER);
        }

        return collect($permissions)
            ->intersect(SecurityPermissions::allKeys())
            ->values()
            ->all();
    }

    public function roleLabel(): string
    {
        return SecurityPermissions::roles()[$this->role] ?? 'Sin rol';
    }

    public function maskedEmail(): string
    {
        [$local, $domain] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        if ($local === '' || $domain === '') {
            return 'correo oculto';
        }

        $prefix = Str::substr($local, 0, 2);

        return $prefix . str_repeat('*', max(3, Str::length($local) - 2)) . '@' . $domain;
    }

    public function visibleEmailFor(?self $viewer): ?string
    {
        return $viewer?->isAdmin() ? $this->email : null;
    }
}
