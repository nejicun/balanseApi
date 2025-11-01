<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserBalanceSeeder extends Seeder
{
    public function run(): void
    {
        // Отключаем массовое присваивание для пароля (он не нужен)
        User::unguard();

        foreach (range(1, 10) as $id) {
            User::updateOrCreate(
                ['id' => $id],
                [
                    'name' => "User {$id}",
                    'email' => "user{$id}@example.com",
                    'password' => '', // не используется
                    'balance' => rand(500, 5000), // случайное число от 500 до 5000
                ]
            );
        }

        User::reguard();
    }
}