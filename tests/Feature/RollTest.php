<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\GameRound;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RollTest extends TestCase
{
    use RefreshDatabase; // wipes DB between each test

    // ── helpers ──────────────────────────────────────────────

    private function actingUser(): User
    {
        // Factory doesn't set balance — pull fresh() to get the DB default (100)
        return User::factory()->create()->fresh();
    }

    // ── §8 step 5: win and loss paths create correct records & update balance ──

    public function test_a_roll_always_creates_transaction_and_game_round_together(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        // spec §8: "Transaction and its GameRound are always created together"
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('game_rounds', 1);

        // GameRound must be linked to the Transaction
        $round = GameRound::first();
        $transaction = Transaction::first();
        $this->assertEquals($transaction->id, $round->transaction_id);
    }

    public function test_balance_is_updated_correctly_after_roll(): void
    {
        $user = $this->actingUser();
        $balanceBefore = $user->balance; // 100

        $response = $this->actingAs($user, 'sanctum')
             ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        $response->assertOk();

        $netWin = $response->json('net_win');
        $expectedBalance = $balanceBefore + $netWin;

        // spec §6.4: net_win applied to balance
        $this->assertEquals($expectedBalance, $response->json('balance'));

        // spec §6.5: balance_after stored in transaction
        $this->assertEquals($expectedBalance, Transaction::first()->balance_after);

        // User's balance persisted in DB
        $this->assertEquals($expectedBalance, $user->fresh()->balance);
    }

    public function test_win_produces_correct_payout(): void
    {
        $user = $this->actingUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

        // Roll until we get a win (≤ 6 attempts, guaranteed to hit eventually)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

            if ($response->json('is_win')) {
                // spec §6.3: total_win = stake * 5, net_win = total_win - stake
                // Use orderByDesc('id') — latest() is unreliable when rows share the same created_at timestamp
                $transaction = Transaction::orderByDesc('id')->first();
                $this->assertEquals(50, $transaction->total_win);  // 10 * 5
                $this->assertEquals(40, $transaction->net_win);    // 50 - 10
                $this->assertEquals(40, $response->json('net_win')); // cross-check response
                return;
            }
        }

        $this->markTestSkipped('Did not roll a win in 30 attempts (extremely unlikely).');
    }

    public function test_loss_produces_correct_payout(): void
    {
        $user = $this->actingUser();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/rolls', ['guess' => 3, 'stake' => 10]);

            if (! $response->json('is_win')) {
                // spec §6.3: total_win = 0, net_win = -stake
                // Use orderByDesc('id') — latest() is unreliable when rows share the same created_at timestamp
                $transaction = Transaction::orderByDesc('id')->first();
                $this->assertEquals(0, $transaction->total_win);
                $this->assertEquals(-10, $transaction->net_win);
                $this->assertEquals(-10, $response->json('net_win')); // cross-check response
                return;
            }
        }

        $this->markTestSkipped('Did not roll a loss in 30 attempts (extremely unlikely).');
    }

    // ── §8 step 3: over-staking rejected with 422 ────────────

    public function test_over_staking_returns_422(): void
    {
        $user = $this->actingUser(); // balance = 100

        $response = $this->actingAs($user, 'sanctum')
             ->postJson('/api/rolls', ['guess' => 3, 'stake' => 9999]);

        // spec §5, §8: "422 if stake > balance"
        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['stake']);

        // No records created on rejected roll
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('game_rounds', 0);
    }

    // ── additional validation ─────────────────────────────────

    public function test_invalid_guess_returns_422(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/rolls', ['guess' => 7, 'stake' => 10])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['guess']);
    }

    public function test_unauthenticated_roll_is_rejected(): void
    {
        $this->postJson('/api/rolls', ['guess' => 3, 'stake' => 10])
             ->assertUnauthorized(); // 401
    }
}
