<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::firstOrCreate(
            ['id' => 1], // search condition
            [
                'username' => 'admin',
                'first_name' => 'Super',
                'last_name' => 'Human',
                'email' => 'admin@example.com',
                'password' => Hash::make('12345678'),
                'status' => 1,
            ]
        );
    }
}
