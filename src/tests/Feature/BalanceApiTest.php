<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase; // ← автоматически очищает БД перед каждым тестом

    /*
    |--------------------------------------------------------------------------
    | Тесты для GET /api/balance/{user_id}
    |--------------------------------------------------------------------------
    */

    public function test_can_get_balance_for_existing_user()
    {
        $user = User::factory()->create(['balance' => 1000.50]);

        $response = $this->getJson("/api/balance/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'user_id' => $user->id,
                     'balance' => 1000.50
                 ]);
    }

    public function test_returns_404_for_non_existing_user()
    {
        $response = $this->getJson('/api/balance/999');

        $response->assertStatus(404)
                 ->assertJson(['error' => 'User not found']);
    }

    public function test_returns_400_for_invalid_user_id()
    {
        $response = $this->getJson('/api/balance/abc');
        $response->assertStatus(400);

        $response = $this->getJson('/api/balance/-1');
        $response->assertStatus(400);
    }

    /*
    |--------------------------------------------------------------------------
    | Тесты для POST /api/deposit
    |--------------------------------------------------------------------------
    */

    public function test_can_deposit_to_existing_user()
    {
        $user = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 200.75,
            'comment' => 'Test deposit'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user_id' => $user->id,
                     'balance' => 700.75
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => 700.75
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 200.75,
            'comment' => 'Test deposit'
        ]);
    }

    public function test_can_deposit_to_new_user()
    {
        $response = $this->postJson('/api/deposit', [
            'user_id' => 999,
            'amount' => 100,
            'comment' => 'First deposit'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user_id' => 999,
                     'balance' => 100
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => 999,
            'balance' => 100
        ]);
    }

    public function test_deposit_validation_fails_with_invalid_data()
    {
        // Отсутствует user_id
        $response = $this->postJson('/api/deposit', [
            'amount' => 100
        ]);
        $response->assertStatus(422);

        // Отрицательная сумма
        $response = $this->postJson('/api/deposit', [
            'user_id' => 1,
            'amount' => -50
        ]);
        $response->assertStatus(422);

        // Нулевая сумма
        $response = $this->postJson('/api/deposit', [
            'user_id' => 1,
            'amount' => 0
        ]);
        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Тесты для POST /api/withdraw
    |--------------------------------------------------------------------------
    */

    public function test_can_withdraw_from_existing_user()
    {
        $user = User::factory()->create(['balance' => 1000]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 300,
            'comment' => 'Test withdraw'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user_id' => $user->id,
                     'balance' => 700
                 ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'withdraw',
            'amount' => 300
        ]);
    }

    public function test_withdraw_fails_if_user_not_found()
    {
        $response = $this->postJson('/api/withdraw', [
            'user_id' => 999,
            'amount' => 100
        ]);

        $response->assertStatus(404)
                 ->assertJson(['error' => 'User not found']);
    }

    public function test_withdraw_fails_if_insufficient_funds()
    {
        $user = User::factory()->create(['balance' => 100]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 150
        ]);

        $response->assertStatus(409)
                 ->assertJson(['error' => 'Insufficient funds']);
    }

    public function test_withdraw_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/withdraw', [
            'user_id' => 1,
            'amount' => -100
        ]);
        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Тесты для POST /api/transfer
    |--------------------------------------------------------------------------
    */

    public function test_can_transfer_between_users()
    {
        $sender = User::factory()->create(['balance' => 1000]);
        $receiver = User::factory()->create(['balance' => 200]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 150,
            'comment' => 'Test transfer'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'from_user_id' => $sender->id,
                     'to_user_id' => $receiver->id,
                     'amount' => 150,
                     'from_balance' => 850,
                     'to_balance' => 350
                 ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $sender->id,
            'type' => 'transfer_out',
            'amount' => 150
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $receiver->id,
            'type' => 'transfer_in',
            'amount' => 150
        ]);
    }

    public function test_transfer_fails_if_sender_not_found()
    {
        $receiver = User::factory()->create();

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => 999,
            'to_user_id' => $receiver->id,
            'amount' => 100
        ]);

        $response->assertStatus(404);
    }

    public function test_transfer_fails_if_receiver_not_found()
    {
        $sender = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $sender->id,
            'to_user_id' => 999,
            'amount' => 100
        ]);

        $response->assertStatus(404);
    }

    public function test_transfer_fails_if_insufficient_funds()
    {
        $sender = User::factory()->create(['balance' => 100]);
        $receiver = User::factory()->create();

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'amount' => 200
        ]);

        $response->assertStatus(409);
    }

    public function test_transfer_fails_if_same_user()
    {
        $user = User::factory()->create(['balance' => 500]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $user->id,
            'to_user_id' => $user->id,
            'amount' => 100
        ]);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Cannot transfer to yourself']);
    }

    public function test_transfer_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/transfer', [
            'from_user_id' => 1,
            'to_user_id' => 2,
            'amount' => -50
        ]);
        $response->assertStatus(422);
    }
}