<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobPlacementSeeder extends Seeder
{
    public function run(): void
    {
        $hrdCompany = Company::where('name', 'PT Kejayaan Terraloka')->first()
            ?? Company::whereHas('user', fn($q) => $q->where('role', 'hrd'))->first();

        if (!$hrdCompany) {
            $hrd = User::where('role', 'hrd')->first();
            $hrdCompany = Company::firstOrCreate(
                ['name' => 'PT Kejayaan Terraloka'],
                [
                    'user_id' => $hrd?->id,
                    'email' => 'hrd@kejayaan-terraloka.com',
                    'is_active' => true,
                ]
            );
        }

        $companies = [
            'PT Astra Honda Motor',
            'PT Telkom Indonesia',
            'PT Hyperdata Solusindo',
            'PT Freeport Indonesia',
            'PT Kopdes Merput',
            'Gojek Tokopedia (GoTo)',
            'PT PLN (Persero)',
            'Shopee Indonesia',
            'PT Petrokimia Gresik',
            'PT United Tractors Tbk',
        ];

        $companyIds = [];
        foreach ($companies as $companyName) {
            $company = Company::firstOrCreate(
                ['name' => $companyName],
                [
                    'email' => strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $companyName)) . '@example.com',
                    'is_active' => true,
                ]
            );
            $companyIds[] = $company->id;
        }

        $otherCompanyIds = Company::where('id', '!=', $hrdCompany->id)->pluck('id')->all();
        if (empty($otherCompanyIds)) {
            $otherCompanyIds = $companyIds;
        }

        $activeStatus = StandardType::byCategory('placement_status')->where('code', 'active')->first();
        $resignedStatus = StandardType::byCategory('placement_status')->where('code', 'resigned')->first();
        $movedStatus = StandardType::byCategory('placement_status')->whereIn('code', ['moved', 'contract_end'])->first();

        $placementConfigs = [
            [
                'accepted' => '2024-01-10',
                'start' => '2024-02-01',
                'status' => $activeStatus?->id,
                'pos' => 'Junior Mechanic',
                'notes' => 'Penempatan batch 1',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Kinerja 3 bulan memuaskan', 'updated_at' => '2024-05-01 10:00:00'],
                    '6' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Target tercapai dengan baik', 'updated_at' => '2024-08-01 10:00:00'],
                    '12' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Diangkat jadi karyawan tetap', 'updated_at' => '2025-02-01 10:00:00'],
                ],
            ],
            [
                'accepted' => '2024-03-15',
                'start' => '2024-04-01',
                'status' => $activeStatus?->id,
                'pos' => 'UI UX Designer',
                'notes' => 'Penempatan divisi Creative',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Desain sesuai standar tim', 'updated_at' => '2024-07-01 09:30:00'],
                    '6' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Mampu memimpin mini sprint', 'updated_at' => '2024-10-01 09:30:00'],
                ],
            ],
            [
                'accepted' => '2024-05-20',
                'start' => '2024-06-01',
                'status' => $movedStatus?->id,
                'pos' => 'Senior Mechanic',
                'notes' => 'Pindah kerja ke mitra lain',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Disiplin dan teliti', 'updated_at' => '2024-09-01 11:00:00'],
                    '6' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Pekerjaan stabil', 'updated_at' => '2024-12-01 11:00:00'],
                    '12' => ['status' => 'Pindah Perusahaan Lain', 'notes' => 'Pindah ke perusahaan mitra', 'updated_at' => '2025-06-01 11:00:00'],
                ],
            ],
            [
                'accepted' => '2024-07-05',
                'start' => '2024-08-01',
                'status' => $resignedStatus?->id,
                'pos' => 'Nambang nyari emas',
                'notes' => 'Resign pindah domisili',
                'evaluations' => [
                    '3' => ['status' => 'Resign / Kontrak Habis', 'notes' => 'Mengundurkan diri pindah domisili', 'updated_at' => '2024-11-01 08:00:00'],
                ],
            ],
            [
                'accepted' => '2024-09-12',
                'start' => '2024-10-01',
                'status' => $activeStatus?->id,
                'pos' => 'Frontend Developer',
                'notes' => 'Penempatan Rekrutmen BKK',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Progres pembuatan komponen sangat baik', 'updated_at' => '2025-01-02 10:00:00'],
                ],
            ],
            [
                'accepted' => '2024-11-18',
                'start' => '2024-12-01',
                'status' => $activeStatus?->id,
                'pos' => 'Teknisi Listrik',
                'notes' => 'Penempatan Industri Mitra',
                'evaluations' => null,
            ],
            [
                'accepted' => '2025-01-08',
                'start' => '2025-02-01',
                'status' => $activeStatus?->id,
                'pos' => 'Staff Administrasi',
                'notes' => 'Penempatan Kampus',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Tertib administrasi', 'updated_at' => '2025-05-02 08:30:00'],
                    '6' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Menguasai filing sistem', 'updated_at' => '2025-08-02 08:30:00'],
                    '12' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Evaluasi tahunan sangat baik', 'updated_at' => '2026-02-02 08:30:00'],
                ],
            ],
            [
                'accepted' => '2025-03-14',
                'start' => '2025-04-01',
                'status' => $movedStatus?->id,
                'pos' => 'Quality Control Operator',
                'notes' => 'Pindah ke cabang industri',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'QC teliti', 'updated_at' => '2025-07-01 09:00:00'],
                    '6' => ['status' => 'Pindah Perusahaan Lain', 'notes' => 'Mendapat tawaran di industri partner', 'updated_at' => '2025-10-01 09:00:00'],
                ],
            ],
            [
                'accepted' => '2025-05-19',
                'start' => '2025-06-01',
                'status' => $activeStatus?->id,
                'pos' => 'Network Engineer',
                'notes' => 'Divisi Infrastruktur',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Paham konfigurasi mikrotik & cisco', 'updated_at' => '2025-09-01 10:15:00'],
                ],
            ],
            [
                'accepted' => '2025-07-22',
                'start' => '2025-08-01',
                'status' => $resignedStatus?->id,
                'pos' => 'Desain Grafis',
                'notes' => 'Resign wirausaha',
                'evaluations' => [
                    '3' => ['status' => 'Resign / Kontrak Habis', 'notes' => 'Resign ingin membuka usaha sendiri', 'updated_at' => '2025-11-01 14:00:00'],
                ],
            ],
            [
                'accepted' => '2025-09-10',
                'start' => '2025-10-01',
                'status' => $activeStatus?->id,
                'pos' => 'Operator Produksi',
                'notes' => 'Penempatan Pabrik Otomotif',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Disiplin shift tinggi', 'updated_at' => '2026-01-05 11:00:00'],
                ],
            ],
            [
                'accepted' => '2025-11-15',
                'start' => '2025-12-01',
                'status' => $activeStatus?->id,
                'pos' => 'IT Support Specialist',
                'notes' => 'Divisi Layanan TI',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Membantu handling tiket helpdesk', 'updated_at' => '2026-03-01 09:00:00'],
                ],
            ],
            [
                'accepted' => '2026-01-07',
                'start' => '2026-02-01',
                'status' => $activeStatus?->id,
                'pos' => 'Drafter AutoCAD',
                'notes' => 'Penempatan Proyek Konstruksi',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Gambar teknik rapi', 'updated_at' => '2026-05-01 10:00:00'],
                ],
            ],
            [
                'accepted' => '2026-02-10',
                'start' => '2026-03-01',
                'status' => $activeStatus?->id,
                'pos' => 'Welding Specialist',
                'notes' => 'Sertifikasi Internasional',
                'evaluations' => [
                    '3' => ['status' => 'Masih Bekerja / Aktif', 'notes' => 'Standar pengelasan lolos uji', 'updated_at' => '2026-06-01 11:30:00'],
                ],
            ],
            [
                'accepted' => '2026-03-12',
                'start' => '2026-04-01',
                'status' => $activeStatus?->id,
                'pos' => 'Content Creator',
                'notes' => 'Divisi Digital Marketing',
                'evaluations' => null,
            ],
            [
                'accepted' => '2026-04-18',
                'start' => '2026-05-01',
                'status' => $activeStatus?->id,
                'pos' => 'Backend Developer',
                'notes' => 'Pengembangan Aplikasi Internal',
                'evaluations' => null,
            ],
            [
                'accepted' => '2026-05-20',
                'start' => '2026-06-01',
                'status' => $activeStatus?->id,
                'pos' => 'Teknisi Kendaraan Ringan',
                'notes' => 'Bengkel Resmi',
                'evaluations' => null,
            ],
            [
                'accepted' => '2026-06-15',
                'start' => '2026-07-01',
                'status' => $movedStatus?->id,
                'pos' => 'Admin Gudang & Logistik',
                'notes' => 'Pindah ke perusahaan logistik lain',
                'evaluations' => null,
            ],
            [
                'accepted' => '2026-07-10',
                'start' => '2026-08-01',
                'status' => $activeStatus?->id,
                'pos' => 'Database Administrator',
                'notes' => 'Manajemen Basis Data',
                'evaluations' => null,
            ],
            [
                'accepted' => '2026-08-05',
                'start' => '2026-09-01',
                'status' => $activeStatus?->id,
                'pos' => 'Technical Support Engineer',
                'notes' => 'Penempatan batch terbaru',
                'evaluations' => null,
            ],
        ];

        $students = StudentAlumni::with('user')->orderBy('id', 'asc')->get();

        $needed = 40 - $students->count();
        if ($needed > 0) {
            $major = Major::first() ?? Major::create([
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'is_active' => true,
            ]);

            for ($i = 0; $i < $needed; $i++) {
                $user = User::factory()->create([
                    'role' => 'alumni',
                ]);

                $newStudent = StudentAlumni::create([
                    'user_id' => $user->id,
                    'major_id' => $major->id,
                    'nis' => (string) (200000 + $students->count() + $i + 1),
                    'graduation_year' => 2025,
                    'is_active' => true,
                ]);

                $students->push($newStudent);
            }
        }

        foreach ($students as $idx => $student) {
            if (!$student->graduation_year) {
                $student->update([
                    'graduation_year' => 2024 + ($idx % 3), // 2024, 2025, 2026
                ]);
            }
        }

        JobPlacement::query()->forceDelete();

        foreach ($students->take(40) as $index => $student) {
            $config = $placementConfigs[$index % count($placementConfigs)];
            $companyId = ($index < 20 && $hrdCompany)
                ? $hrdCompany->id
                : $otherCompanyIds[array_rand($otherCompanyIds)];

            $student->update([
                'current_position' => $config['pos'],
                'current_company_id' => $companyId,
            ]);

            JobPlacement::create([
                'student_alumni_id' => $student->id,
                'company_id' => $companyId,
                'placement_status_id' => $config['status'],
                'accepted_date' => $config['accepted'],
                'start_date' => $config['start'],
                'notes' => $config['notes'],
                'evaluations' => $config['evaluations'],
            ]);
        }
    }
}
