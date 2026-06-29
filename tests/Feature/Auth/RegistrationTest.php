<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_first_user_is_registered_as_admin_and_authenticated(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user->approved_at);
        $this->assertSame('admin', $user->role);
    }

    public function test_additional_users_register_as_pending_and_are_not_authenticated(): void
    {
        User::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));

        $user = User::where('email', 'pending@example.com')->first();

        $this->assertNull($user->approved_at);
        $this->assertSame('consulta', $user->role);
    }
}
