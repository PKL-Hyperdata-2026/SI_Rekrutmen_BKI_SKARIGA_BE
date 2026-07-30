<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'Jon Snow',
            'email' => 'jon.snow@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
