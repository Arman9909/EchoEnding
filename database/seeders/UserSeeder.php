<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin1',
                'email' => 'kuganov.00@gmail.com',
                'password' => Hash::make('Arman_7007_'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin2',
                'email' => 'exxxar@gmail.com',
                'password' => Hash::make('exxxar756'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
