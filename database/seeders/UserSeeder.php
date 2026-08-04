<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // User::create([
        //     'full_name' => 'Jon Snow',
        //     'email' => 'jon.snow@example.com',
        //     'phone' => '081234567890',
        //     'password' => 'password',
        //     'role' => 'admin',
        //     'is_active' => true,
        // ]);

        // User::factory(10)->create(['role' => 'siswa']);

        User::create([
            'full_name' => 'Siswa',
            'email' => 'siswa@email.com',
            'phone' => '081234567890',
            'password' => 'siswa123',
            'role' => 'siswa',
            'is_active' => true,
        ]);

        User::create([
            'full_name' => 'Alumni',
            'email' => 'alumni@email.com',
            'phone' => '081234567890',
            'password' => 'alumni123',
            'role' => 'alumni',
            'is_active' => true,
        ]);

        User::create([
            'full_name' => 'Admin',
            'email' => 'admin@email.com',
            'phone' => '081234567890',
            'password' => 'admin123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'full_name' => 'HRD',
            'email' => 'hrd@email.com',
            'phone' => '081234567890',
            'password' => 'hrd123',
            'role' => 'hrd',
            'is_active' => true,
        ]);
    }
}
