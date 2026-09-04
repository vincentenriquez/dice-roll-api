<?php

namespace App\Services;

use App\Models\GameRound;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RollService
{
    private const PAYOUT_MULTIPLIER = 5;

    public function play(User $user, int $guess, int $stake, ?callable $roller = null): array
    {
        // Win/Loss Calculation
        $result = $roller ? $roller() : random_int(1, 6);
        $isWin = ($guess === $result);
        $totalWin = $isWin ? $stake * self::PAYOUT_MULTIPLIER : 0;
        $netWin = $totalWin - $stake;

        // Apply balance and save both records atomically
        DB::transaction(function () use ($user, $guess, $stake, $result, $isWin, $totalWin, $netWin) {
            // Update balance: use decrement() for losses since increment() only accepts positive values
            if ($netWin >= 0) {
                $user->increment('balance', $netWin);
            } else {
                $user->decrement('balance', abs($netWin));
            }

            // Update win streak
            if ($isWin) {
                $user->increment('win_streak');
            } else {
                $user->update(['win_streak' => 0]);
            }

            if ($netWin > 0 && $netWin > $user->biggest_win) {
                $user->update(['biggest_win' => $netWin]);
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'stake' => $stake,
                'total_win' => $totalWin,
                'net_win' => $netWin,
                'balance_after' => $user->balance,
            ]);

            GameRound::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'guess' => $guess,
                'result' => $result,
                'is_win' => $isWin,
            ]);
        });

        return [
            'result' => $result,
            'is_win' => $isWin,
            'net_win' => $netWin,
            'balance_after' => $user->balance,
        ];
    }
}
