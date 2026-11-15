<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomerOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_customer_can_register_with_email_and_receives_otp(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Customer Baru',
            'email' => 'customer.baru@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('customer.verification.notice'));
        $this->assertAuthenticated();

        $user = User::where('email', 'customer.baru@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_expires_at);
        $this->assertNotEmpty($user->username);

        Notification::assertSentTo($user, CustomerOtpNotification::class);
    }

    public function test_unverified_customer_is_redirected_to_verification_page(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $this->actingAs($customer)
            ->get('/customer')
            ->assertRedirect(route('customer.verification.notice'));
    }

    public function test_customer_can_verify_email_with_otp(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
        ]);

        $this->actingAs($customer)
            ->post('/customer/verify', ['code' => '123456'])
            ->assertRedirect(route('customer.home'));

        $customer->refresh();

        $this->assertNotNull($customer->email_verified_at);
        $this->assertNull($customer->otp_code);
    }

    public function test_customer_can_login_using_email(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email' => 'login.email@example.com',
        ]);

        $this->post('/login', [
            'username' => 'login.email@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($customer);
    }

    public function test_google_login_is_hidden_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_google_login_redirects_when_configured(): void
    {
        config([
            'services.google.client_id' => 'dummy-id',
            'services.google.client_secret' => 'dummy-secret',
            'services.google.redirect' => 'http://localhost/callback',
        ]);

        $response = $this->get('/auth/google/redirect');

        $this->assertTrue(str_starts_with($response->headers->get('Location') ?? '', 'https://accounts.google.com'));
    }
}
