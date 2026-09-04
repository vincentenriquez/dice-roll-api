<?php

namespace App\Http\Controllers;

use App\Services\RollService;
use App\Http\Requests\RollRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RollController extends Controller
{
    public function __construct(private RollService $rollService) {}

    public function store(RollRequest $request): JsonResponse
    {
        // Validation is automatically handled by RollRequest
        $validated = $request->validated();

        $outcome = $this->rollService->play(
            $request->user(),
            $validated['guess'],
            $validated['stake']
        );

        // §6.6 — Return result + updated balance
        return response()->json([
            'guess' => $validated['guess'],
            'stake' => $validated['stake'],
            'result' => $outcome['result'],
            'is_win' => $outcome['is_win'],
            'net_win' => $outcome['net_win'],
            'balance' => $outcome['balance_after'],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $rounds = $request->user()
            ->gameRounds()
            ->with('transaction')
            ->latest()
            ->paginate(15);

        return response()->json($rounds);
    }
}
