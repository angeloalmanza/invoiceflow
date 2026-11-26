<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_user_and_token(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Mario', 'email' => 'mario@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonStructure(['user', 'token']);
    }

    public function test_register_requires_matching_password_of_8_chars(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Mario', 'email' => 'mario@example.com',
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'sbagliata'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_then_me_then_logout(): void
    {
        $user = User::factory()->create();

        $token = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('email', $user->email);
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/invoices')->assertUnauthorized();
        $this->getJson('/api/clients')->assertUnauthorized();
    }
}
