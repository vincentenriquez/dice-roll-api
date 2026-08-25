<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── POST /api/register ───────────────────────────────────────

    public function test_register_creates_user_with_default_balance(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Vincent',
            'email'    => 'vincent@example.com',
            'password' => 'password123',
        ]);

        // spec §5: 201 on successful registration
        $response->assertCreated(); // 201
        $response->assertJsonPath('balance', 100); // spec §7.1: default balance = 100

        $this->assertDatabaseHas('users', [
            'email' => 'vincent@example.com',
        ]);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'vincent@example.com']);

        $response = $this->postJson('/api/register', [
            'name'     => 'Vincent',
            'email'    => 'vincent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Vincent',
            'email'    => 'vincent@example.com',
            'password' => '123',           // min:8 rule
        ]);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    // ── POST /api/login ──────────────────────────────────────────

    public function test_login_returns_token_and_balance(): void
    {
        $user = User::factory()->create([
            'email'    => 'vincent@example.com',
            'password' => bcrypt('password123'),
        ])->fresh();

        $response = $this->postJson('/api/login', [
            'email'    => 'vincent@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk(); // 200
        $response->assertJsonStructure(['user', 'token', 'balance']);

        // Token must actually be stored in DB
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'vincent@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'vincent@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertUnprocessable(); // 422 — ValidationException
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable(); // 422
    }

    // ── POST /api/logout ─────────────────────────────────────────

    public function test_logout_revokes_all_tokens(): void
    {
        $user = User::factory()->create()->fresh();

        // Create two tokens (simulating register + login)
        $user->createToken('session_1');
        $user->createToken('session_2');
        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Logout using one of the sessions
        $token = $user->createToken('session_3')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 3);

        $this->withToken($token)
             ->postJson('/api/logout')
             ->assertOk()
             ->assertJsonPath('message', 'Logged out successfully');

        // spec: tokens()->delete() wipes ALL tokens for this user
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')
             ->assertUnauthorized(); // 401
    }
}
