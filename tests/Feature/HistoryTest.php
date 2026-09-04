<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    // ── GET /api/me/balance ──────────────────────────────

    public function test_balance_endpoint_returns_current_balance(): void
    {
        $user = User::factory()->create(['balance' => 250])->fresh();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/balance')
            ->assertOk()
            ->assertJsonPath('balance', 250);
    }

    public function test_balance_requires_authentication(): void
    {
        $this->getJson('/api/me/balance')
            ->assertUnauthorized(); // 401
    }

    // ── GET /api/me/transactions ─────────────────────────

    public function test_transactions_returns_paginated_list(): void
    {
        $user = User::factory()->create()->fresh();

        // Make 3 rolls to generate transactions
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'stake', 'total_win', 'net_win', 'balance_after']],
                'current_page',
                'total',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    // ── GET /api/rolls ───────────────────────────────────

    public function test_rolls_returns_paginated_game_rounds_with_transaction(): void
    {
        $user = User::factory()->create()->fresh();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rolls');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'guess', 'result', 'is_win', 'transaction']
                ],
            ]);
    }

    public function test_user_only_sees_their_own_history(): void
    {
        $userA = User::factory()->create()->fresh();
        $userB = User::factory()->create()->fresh();

        // UserA makes a roll
        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        // UserB should see 0 transactions
        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/me/transactions');

        $this->assertCount(0, $response->json('data'));
    }

    public function test_balance_reset_sets_balance_to_100(): void
    {

        $user = User::factory()->create(['balance' => 5]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/me/balance/reset');
        $response->assertOk()
                 ->assertJsonFragment(['balance' => 100]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 100]);
    }

    public function test_balance_reset_requires_authentication(): void
    {
        $response = $this->postJson('/api/me/balance/reset');
        $response->assertUnauthorized();
    }

    public function test_win_streak_increments_on_win(): void
    {
        $user = User::factory()->create(['win_streak' => 2, 'balance' => 100]);
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);
        $response->assertOk();
    }

    public function test_win_streak_resets_on_loss(): void
    {
        $user = User::factory()->create(['win_streak' => 5, 'balance' => 5]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/rolls', ['guess' => 4, 'stake' => 10]);
        $response->assertUnprocessable();
    }

    public function test_biggest_win_is_updated_on_win(): void
    {
        $user = User::factory()->create(['biggest_win' => 0])->fresh();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/rolls', ['guess' => 3, 'stake' => 10])->assertOk();
        $response->assertOk();
    }

    public function test_biggest_win_is_not_updated_on_loss(): void
    {
        $user = User::factory()->create(['biggest_win' => 40])->fresh();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/rolls', ['guess' => 4, 'stake' => 10])->assertOk();
        $response->assertOk();
    }

    public function test_balance_not_updated_if_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 5]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/rolls', ['guess' => 3, 'stake' => 10])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 5]);
    }
}
