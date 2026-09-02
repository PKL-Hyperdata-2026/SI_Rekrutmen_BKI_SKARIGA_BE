<?php

namespace Database\Seeders;

use App\Models\StudentAlumni;
use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StudentAlumniSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            "Marvello Faisal",
            "Surya Jayanata Wibawa",
            "Mochammad Dafa AL Za'biy Nazarudin",
            "Noval Abiansyah Tegar",
            "Mirza Adliansyah Pratama",
            "Muhammad Tiansyah Wahyudi Putra",
            "Nouval Adibayu Kencono",
            "Muhammad Rafif Rabbani",
            "Shaila Tri Febrianti",
            "Aulyvia Amalina",
            "Kadek Giovani Putra Andika",
            "Budi Santoso",
            "Siti Aminah",
            "Agus Pratama",
            "Dewi Lestari",
            "Rizky Hidayat",
            "Putri Maharani",
            "Hendra Saputra",
            "Ayu Ningsih",
            "Aditya Kusuma"
        ];

        $rpl = Major::firstOrCreate(
            ['code' => 'RPL'],
            ['name' => 'Rekayasa Perangkat Lunak', 'is_active' => true]
        );

        foreach ($names as $index => $name) {
            $isStudent = $index < 10;

            $user = User::factory()->create([
                'full_name' => $name,
                'email' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . '@gmail.com',
                'role' => $isStudent ? 'siswa' : 'alumni',
            ]);

            $data = [
                'user_id' => $user->id,
                'nis' => '2000' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT),
                'graduation_year' => $isStudent ? null : 2024,
                'current_position' => $isStudent ? null : 'IT Support',
                'starting_salary' => $isStudent ? null : 4500000,
                'waiting_time_months' => $isStudent ? null : 1,
                'social_media' => ['linkedin' => 'https://linkedin.com/in/' . Str::slug($name)],
            ];

            if ($isStudent) {
                $data['major_id'] = $rpl->id;
            }

            StudentAlumni::factory()->create($data);
        }
    }
}
