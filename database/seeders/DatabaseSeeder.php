<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Если хотите оставить пример фабрики — можно, но не обязательно:
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Запуск вашего кастомного сидера
        $this->call([
            UsersTableSeeder::class,
        ]);
    }
}
