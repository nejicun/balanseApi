<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BalanceController;

Route::get('/balance/{user_id}', [BalanceController::class, 'showBalance']);
Route::post('/deposit', [BalanceController::class, 'deposit']);
Route::post('/withdraw', [BalanceController::class, 'withdraw']);
Route::post('/transfer', [BalanceController::class, 'transfer']);
Route::get('/transactions/{user_id}', [BalanceController::class, 'getUserTransactions']);