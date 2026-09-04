<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BalTranController;
use App\Http\Controllers\RollController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/rolls', [RollController::class, 'store'])->name('rolls.store');
    Route::get('/rolls', [RollController::class, 'index'])->name('rolls.index');
    Route::get('/me/balance', [BalTranController::class, 'balance'])->name('me.balance');
    Route::get('/me/transactions', [BalTranController::class, 'transactions'])->name('me.transactions');
    Route::post('/me/balance/reset', [BalTranController::class, 'resetBalance'])->name('balance.reset');
});
