<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    /**
     * Получить текущий баланс пользователя.
     *
     * @param  int  $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showBalance($user_id)
    {
        // Валидация: user_id должен быть положительным числом
        if (!is_numeric($user_id) || $user_id <= 0) {
            return response()->json([
                'error' => 'Invalid user_id'
            ], 400);
        }

        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        return response()->json([
            'user_id' => (int) $user->id,
            'balance' => (float) $user->balance,
        ]);
    }
public function deposit(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|integer|min:1',
        'amount' => 'required|numeric|min:0.01',
        'comment' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $validator->errors()
        ], 422);
    }

    $userId = $request->user_id;
    $amount = $request->amount;
    $comment = $request->comment;

    $result = DB::transaction(function () use ($userId, $amount, $comment) {
        // Находим или создаём пользователя
        $user = User::find($userId);
        if (!$user) {
            $user = User::create([
                'id' => $userId,
                'name' => "User {$userId}",
                'email' => "user{$userId}@example.com",
                'password' => '',
                'balance' => 0,
            ]);
        }

        // Пополняем баланс
        $user->balance += $amount;
        $user->save();

        // Записываем транзакцию
        Transaction::create([
            'user_id' => $userId,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $user->balance,
            'comment' => $comment,
        ]);

        return [
            'user_id' => $user->id,
            'balance' => (float) $user->balance,
        ];
    });

    return response()->json($result);
}

public function withdraw(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|integer|min:1',
        'amount' => 'required|numeric|min:0.01',
        'comment' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $validator->errors()
        ], 422);
    }

    $userId = $request->user_id;
    $amount = $request->amount;

    // Проверяем, существует ли пользователь
    $user = User::find($userId);
    if (!$user) {
        return response()->json([
            'error' => 'User not found'
        ], 404);
    }

    // Проверяем баланс
    if ($user->balance < $amount) {
        return response()->json([
            'error' => 'Insufficient funds'
        ], 409);
    }

    // Выполняем операцию в транзакции
    DB::transaction(function () use ($user, $amount, $request) {
        $user->balance -= $amount;
        $user->save();

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => $amount,
            'balance_after' => $user->balance,
            'comment' => $request->comment,
        ]);
    });

    return response()->json([
        'user_id' => $user->id,
        'balance' => (float) $user->balance,
    ]);
}

public function transfer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'from_user_id' => 'required|integer|min:1',
        'to_user_id' => 'required|integer|min:1',
        'amount' => 'required|numeric|min:0.01',
        'comment' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => 'Validation failed',
            'messages' => $validator->errors()
        ], 422);
    }

    $fromId = $request->from_user_id;
    $toId = $request->to_user_id;
    $amount = $request->amount;
    $comment = $request->comment;

    // Нельзя переводить самому себе
    if ($fromId === $toId) {
        return response()->json([
            'error' => 'Cannot transfer to yourself'
        ], 400);
    }

    
    $result = DB::transaction(function () use ($fromId, $toId, $amount, $comment) {
        // Проверяем обоих пользователей
        $fromUser = User::find($fromId);
        $toUser = User::find($toId);

        if (!$fromUser) {
            return response()->json(['error' => 'Sender not found'], 404);
        }
        if (!$toUser) {
            return response()->json(['error' => 'Recipient not found'], 404);
        }

        // Проверка баланса
        if ($fromUser->balance < $amount) {
            return response()->json(['error' => 'Insufficient funds'], 409);
        }

        // Списываем у отправителя
        $fromUser->balance -= $amount;
        $fromUser->save();

        // Зачисляем получателю
        $toUser->balance += $amount;
        $toUser->save();

       
        Transaction::create([
            'user_id' => $fromId,
            'type' => 'transfer_out',
            'amount' => $amount,
            'balance_after' => $fromUser->balance,
            'comment' => $comment,
        ]);

        Transaction::create([
            'user_id' => $toId,
            'type' => 'transfer_in',
            'amount' => $amount,
            'balance_after' => $toUser->balance,
            'comment' => $comment,
        ]);

        return [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'amount' => (float) $amount,
            'from_balance' => (float) $fromUser->balance,
            'to_balance' => (float) $toUser->balance,
        ];
    });

    
    if ($result instanceof \Illuminate\Http\JsonResponse) {
        return $result;
    }

    return response()->json($result);
}

public function getUserTransactions($user_id)
{
    if (!is_numeric($user_id) || $user_id <= 0) {
        return response()->json(['error' => 'Invalid user_id'], 400);
    }

    $user = User::find($user_id);
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $transactions = Transaction::where('user_id', $user_id)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($t) {
            return [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'balance_after' => (float) $t->balance_after,
                'comment' => $t->comment,
                'created_at' => $t->created_at->format('Y-m-d H:i:s'),
            ];
        });

    return response()->json([
        'user_id' => $user->id,
        'transactions' => $transactions,
    ]);
}

public function index()
{
    $users = User::select('id', 'name', 'balance')->orderBy('id')->get();
    return view('welcome', compact('users'));
}
}