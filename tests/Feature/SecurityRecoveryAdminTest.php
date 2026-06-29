<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\SecurityPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRecoveryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_admin_can_not_be_disabled_or_demoted(): void
    {
        config(['security.recovery_admin_email' => 'kevinavila062@gmail.com']);

        $actor = User::factory()->create();
        $recovery = User::factory()->create([
            'email' => 'kevinavila062@gmail.com',
            'role' => SecurityPermissions::ADMIN,
            'approved_at' => now(),
            'disabled_at' => null,
        ]);

        $response = $this->actingAs($actor)->put(route('seguridad.usuarios.update', $recovery), [
            'role' => SecurityPermissions::VIEWER,
            'permissions' => [],
            'approved' => false,
            'disabled' => true,
        ]);

        $response->assertSessionHasErrors('user');

        $recovery->refresh();

        $this->assertSame(SecurityPermissions::ADMIN, $recovery->role);
        $this->assertNotNull($recovery->approved_at);
        $this->assertNull($recovery->disabled_at);
    }

    public function test_recovery_admin_can_not_change_email_or_delete_profile(): void
    {
        config(['security.recovery_admin_email' => 'kevinavila062@gmail.com']);

        $recovery = User::factory()->create([
            'email' => 'kevinavila062@gmail.com',
            'role' => SecurityPermissions::ADMIN,
            'approved_at' => now(),
            'disabled_at' => null,
        ]);

        $this->actingAs($recovery)
            ->patch(route('profile.update'), [
                'name' => $recovery->name,
                'email' => 'otro@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($recovery)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('users', [
            'id' => $recovery->id,
            'email' => 'kevinavila062@gmail.com',
        ]);
    }

    public function test_admin_can_delete_regular_users_but_not_recovery_or_self(): void
    {
        config(['security.recovery_admin_email' => 'kevinavila062@gmail.com']);

        $admin = User::factory()->create();
        $regular = User::factory()->create([
            'role' => SecurityPermissions::VIEWER,
        ]);
        $recovery = User::factory()->create([
            'email' => 'kevinavila062@gmail.com',
            'role' => SecurityPermissions::ADMIN,
            'approved_at' => now(),
            'disabled_at' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('seguridad.usuarios.destroy', $regular))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $regular->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'security.user_deleted']);

        $this->actingAs($admin)
            ->delete(route('seguridad.usuarios.destroy', $recovery))
            ->assertSessionHasErrors('user');

        $this->actingAs($admin)
            ->delete(route('seguridad.usuarios.destroy', $admin))
            ->assertSessionHasErrors('user');
    }

    public function test_admin_can_delete_audit_logs_and_purge_filtered_logs(): void
    {
        $admin = User::factory()->create();
        $loginLog = AuditLog::create([
            'user_id' => $admin->id,
            'event' => 'auth.login',
            'description' => 'Inicio de sesion correcto.',
            'created_at' => now(),
        ]);
        $nominaLog = AuditLog::create([
            'user_id' => $admin->id,
            'event' => 'nomina.updated',
            'description' => 'Nomina actualizada.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('seguridad.auditoria.destroy', $loginLog))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('audit_logs', ['id' => $loginLog->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'audit_log.deleted']);

        $this->actingAs($admin)
            ->delete(route('seguridad.auditoria.purge'), ['event' => 'nomina.updated'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('audit_logs', ['id' => $nominaLog->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'audit_log.purged']);
    }

    public function test_non_admin_audit_viewer_does_not_receive_real_emails(): void
    {
        $viewer = User::factory()->create([
            'role' => SecurityPermissions::MANAGER,
            'permissions' => ['dashboard.view', 'sistema.audit'],
        ]);
        $target = User::factory()->create([
            'name' => 'Persona Auditada',
            'email' => 'qa@example.com',
        ]);

        AuditLog::create([
            'user_id' => $target->id,
            'event' => 'auth.login',
            'description' => 'Inicio de sesion correcto.',
            'metadata' => ['email' => $target->email],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('seguridad.auditoria.index'));

        $response->assertOk();
        $response->assertDontSee('qa@example.com', false);
        $response->assertSee('qa***@example.com', false);
        $response->assertSee('correo oculto', false);
    }
}
