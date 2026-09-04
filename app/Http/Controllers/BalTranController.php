<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\BalTranService;

class BalTranController extends Controller
{
    public function __construct(private BalTranService $balTranService) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'balance' => $user->balance,
            'win_streak' => $user->win_streak,
            'biggest_win' => $user->biggest_win,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()->transactions()->latest('created_at')->paginate(15);

        return response()->json($transactions);
    }

    public function resetBalance(Request $request): JsonResponse
    {
        $this->balTranService->resetBalance($request->user());

        return response()->json(['balance' => 100]);
    }
}
