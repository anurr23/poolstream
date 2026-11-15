<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('landing'));
    }

    public function test_admin_is_always_redirected_to_landing_page_even_with_intended_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->withSession(['url.intended' => '/dashboard'])
            ->post('/login', [
                'username' => $admin->username,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('landing'));
    }

    public function test_kasir_is_redirected_to_dashboard(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);

        $response = $this->post('/login', [
            'username' => $kasir->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
