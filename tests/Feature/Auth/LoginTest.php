<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/user/login')->assertOk();
    }

    public function test_valid_credentials_log_the_user_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post('/user/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('user_logins', [
            'user_id' => $user->id,
            'auth_method' => 'password',
        ]);
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post('/user/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $response = $this->post('/user/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'is_suspended' => true,
        ]);

        $response = $this->post('/user/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_is_required(): void
    {
        $this->post('/user/login', ['password' => 'whatever'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_is_required(): void
    {
        $this->post('/user/login', ['email' => 'someone@example.com'])
            ->assertSessionHasErrors('password');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/user/logout')
            ->assertRedirect('/user/login');

        $this->assertGuest();
    }
}
