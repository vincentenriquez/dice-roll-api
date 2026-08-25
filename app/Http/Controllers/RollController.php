<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RollService;

class RollController extends Controller
{
    public function store(Request $request)
    {
        // §6.1 — Validate inputs
        $validated = $request->validate([
            'guess' => 'required|integer|min:1|max:6',
            'stake' => ['required', 'integer', 'min:1',
                        'max:' . $request->user()->balance],
        ]);

        $outcome = (new RollService)->play(
            $request->user(), 
            $validated['guess'], 
            $validated['stake']);

        // // §6.2 — Server rolls the die (NEVER from client)
        // $result = random_int(1, 6);

        // // §6.3 — Win/loss calculation
        // $isWin    = ($validated['guess'] === $result);
        // $totalWin = $isWin ? $validated['stake'] * 5 : 0;
        // $netWin   = $totalWin - $validated['stake'];  // negative on loss

        // // §6.4 + §6.5 — Apply balance and save both records atomically
        // DB::transaction(function () use ($user, $validated, $result, $isWin, $totalWin, $netWin) {
        //     $user->balance += $netWin;
        //     $user->save();

        //     $transaction = Transaction::create([
        //         'user_id'       => $user->id,
        //         'stake'         => $validated['stake'],
        //         'total_win'     => $totalWin,
        //         'net_win'       => $netWin,
        //         'balance_after' => $user->balance,
        //     ]);

        //     GameRound::create([
        //         'user_id'        => $user->id,
        //         'transaction_id' => $transaction->id,
        //         'guess'          => $validated['guess'],
        //         'result'         => $result,
        //         'is_win'         => $isWin,
        //     ]);
        // });

        // §6.6 — Return result + updated balance
        return response()->json([
            'guess'           => $validated['guess'],
            'stake'           => $validated['stake'],
            'result'          => $outcome['result'],
            'is_win'          => $outcome['is_win'],
            'net_win'         => $outcome['net_win'],
            'balance'         => $request->user()->fresh()->balance,
        ]);
    }

    public function index(Request $request) 
    {
        $rounds = $request->user()
            ->gameRounds()
            ->with('transaction')
            ->latest()
            ->paginate(15);

        return response()->json($rounds);
    }

}
