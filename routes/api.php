<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RollController;
use App\Http\Controllers\BalTranController;

Route::post('/register', [AuthController::class, 'register'])->name('AuthController.register');
Route::post('/login', [AuthController::class, 'login'])->name('AuthController.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class,'logout'])->name('AuthController.logout');
    Route::post('/rolls', [RollController::class, 'store'])->name('RollController.store');
    Route::get('/rolls', [RollController::class, 'index'])->name('RollController.index');
    Route::get('/me/balance', [BalTranController::class, 'balance'])->name('BalTranController.balance');
    Route::get('/me/transactions', [BalTranController::class, 'transactions'])->name('BalTranController.transactions');
});        
