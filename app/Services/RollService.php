<?php

namespace App\Services;

use App\Models\GameRound;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

class RollService
{
    public function play(User $user, int $guess, int $stake, ? callable $roller = null): array
    {
        //Win/Loss Calculation
        $result = $roller ? $roller() : random_int(1, 6);
        $isWin = ($guess === $result);
        $totalWin = $isWin ? $stake * 5 : 0;
        $netWin = $totalWin - $stake;

        //Apply balance and save both records atomically
        DB::transaction(function () use ($user, $guess, $stake, $result, $isWin, $totalWin, $netWin) {
            $user->balance += $netWin;
            $user->save();

            $transaction = Transaction::create([
                'user_id'       => $user->id,
                'stake'         => $stake,
                'total_win'     => $totalWin,
                'net_win'       => $netWin,
                'balance_after' => $user->balance,
            ]);

            GameRound::create([
                'user_id'        => $user->id,
                'transaction_id' => $transaction->id,
                'guess'          => $guess,
                'result'         => $result,
                'is_win'         => $isWin,
            ]);
        });

        return [
            //'guess'   => $guess,
            'result'  => $result,
            'is_win'  => $isWin,
            'net_win' => $netWin,
        ];
    }
}