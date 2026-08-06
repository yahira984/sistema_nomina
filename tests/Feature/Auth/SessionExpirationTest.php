<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SessionExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_lifetime_is_one_hour(): void
    {
        $this->assertSame(60, config('session.lifetime'));
    }

    public function test_authenticated_activity_can_refresh_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('session.keep-alive'))
            ->assertNoContent();
    }

    public function test_guest_cannot_refresh_a_session(): void
    {
        $this->get(route('session.keep-alive'))
            ->assertRedirect(route('login', ['expired' => 1]));
    }

    public function test_login_does_not_create_a_persistent_remember_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }
}
