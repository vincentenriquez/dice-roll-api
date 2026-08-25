<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BalTranController extends Controller
{
    public function balance(Request $request) 
    {
        return response()->json(['balance' => $request->user()->balance]);
    }

    public function transactions(Request $request) 
    {
        $transactions = $request->user()->transactions()->latest()->paginate(15);
        return response()->json($transactions);
    }
}
