<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class UserSeeder extends Seeder
{
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

        User::factory(10)->create(['role' => 'siswa']);
    }
}
