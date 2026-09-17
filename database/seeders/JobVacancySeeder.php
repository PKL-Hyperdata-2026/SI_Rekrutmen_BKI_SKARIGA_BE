<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobVacancy;
use App\Models\Major;
use App\Models\StandardType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobVacancySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::where('role', 'admin')->first();
            $hrd = User::where('role', 'hrd')->first() ?? $admin;
            $creatorId = $admin?->id ?? 1;

            $statusPublished = StandardType::byCategory('vacancy_status')->where('code', 'published')->first();
            $jobTypeFullTime = StandardType::byCategory('job_type')->where('code', 'full_time')->first();
            $jobTypeContract = StandardType::byCategory('job_type')->where('code', 'contract')->first() ?? $jobTypeFullTime;
            $jobTypeInternship = StandardType::byCategory('job_type')->where('code', 'internship')->first() ?? $jobTypeFullTime;

            $targetClass12 = StandardType::byCategory('target_applicant')->where('code', 'class_12_only')->first();
            $targetBoth = StandardType::byCategory('target_applicant')->where('code', 'class_12_and_alumni')->first();
            $targetAlumni = StandardType::byCategory('target_applicant')->where('code', 'alumni_only')->first();

            $industryAutomotive = StandardType::byCategory('company_industry')->where('code', 'manufacturing_automotive')->first();
            $industryTelecom = StandardType::byCategory('company_industry')->where('code', 'telecommunication_networking')->first();
            $industryHeavy = StandardType::byCategory('company_industry')->where('code', 'machinery_heavy_equipment')->first();
            $industrySoftware = StandardType::byCategory('company_industry')->where('code', 'software_house_it')->first() ?? $industryTelecom;
            $industryCreative = StandardType::byCategory('company_industry')->where('code', 'multimedia_creative_agency')->first() ?? $industryTelecom;

            $astra = Company::firstOrCreate(
                ['name' => 'PT Astra Honda Motor'],
                [
                    'email' => 'karir@astra-honda.com',
                    'phone' => '021-6518080',
                    'address' => 'Kawasan Industri Suryacipta, Karawang, Jawa Barat',
                    'website' => 'https://www.astra-honda.com',
                    'pic_name' => 'Budi Santoso',
                    'pic_contact' => '081234567891',
                    'industry_id' => $industryAutomotive?->id,
                    'user_id' => $hrd?->id ?? $creatorId,
                    'is_active' => true,
                    'created_by' => $creatorId,
                ]
            );

            $telkom = Company::firstOrCreate(
                ['name' => 'PT Telkom Akses'],
                [
                    'email' => 'recruitment@telkomakses.co.id',
                    'phone' => '0341-555666',
                    'address' => 'Jl. Jenderal Basuki Rahmat No. 7-9, Kota Malang, Jawa Timur',
                    'website' => 'https://www.telkomakses.co.id',
                    'pic_name' => 'Siti Rahma',
                    'pic_contact' => '081234567892',
                    'industry_id' => $industryTelecom?->id,
                    'user_id' => $hrd?->id ?? $creatorId,
                    'is_active' => true,
                    'created_by' => $creatorId,
                ]
            );

            $unitedTractors = Company::firstOrCreate(
                ['name' => 'PT United Tractors Tbk'],
                [
                    'email' => 'career@unitedtractors.com',
                    'phone' => '031-8671234',
                    'address' => 'Jl. Rungkut Industri III No. 55, Surabaya, Jawa Timur',
                    'website' => 'https://www.unitedtractors.com',
                    'pic_name' => 'Dedi Kurniawan',
                    'pic_contact' => '081234567893',
                    'industry_id' => $industryHeavy?->id,
                    'user_id' => $hrd?->id ?? $creatorId,
                    'is_active' => true,
                    'created_by' => $creatorId,
                ]
            );

            $teknoMaju = Company::firstOrCreate(
                ['name' => 'PT Teknologi Maju Indonesia'],
                [
                    'email' => 'hrd@teknologimaju.co.id',
                    'phone' => '081234567890',
                    'address' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan',
                    'website' => 'https://www.teknologimaju.co.id',
                    'pic_name' => 'Budi Santoso',
                    'pic_contact' => '081298765432',
                    'industry_id' => $industrySoftware?->id,
                    'user_id' => $hrd?->id ?? $creatorId,
                    'is_active' => true,
                    'created_by' => $creatorId,
                ]
            );

            $multimediaKreatif = Company::firstOrCreate(
                ['name' => 'PT Multinedia Kreatif'],
                [
                    'email' => 'kerja@multimedia-kreatif.id',
                    'phone' => '084422233344',
                    'address' => 'Jl. Gatot Subroto No. 100, Jakarta Selatan',
                    'website' => 'https://www.multimedia-kreatif.id',
                    'pic_name' => 'Rina Marlina',
                    'pic_contact' => '084488877766',
                    'industry_id' => $industryCreative?->id,
                    'user_id' => $hrd?->id ?? $creatorId,
                    'is_active' => true,
                    'created_by' => $creatorId,
                ]
            );

            $majorMap = Major::all()->keyBy('code');

            $vacanciesData = [
                [
                    'company_id' => $astra->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetClass12?->id,
                    'title' => 'Lowongan Junior Assembly & Maintenance',
                    'slug' => 'junior-assembly-maintenance-astra-honda',
                    'position' => 'Junior Assembly & Maintenance',
                    'description' => 'Perakitan komponen otomotif, pemeriksaan fisik produk, serta maintenance dasar peralatan pabrik.',
                    'qualification' => "1. Siswa aktif kelas 12 SMK jurusan TKJ / TKR\n2. Sehat jasmani dan rohani\n3. Bersedia ditempatkan di Karawang, Jawa Barat",
                    'quota' => 25,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Karawang, Jabar',
                    'min_salary' => 5200000,
                    'max_salary' => 6500000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKJ', 'TKR'],
                ],
                [
                    'company_id' => $telkom->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetBoth?->id,
                    'title' => 'Lowongan Network & Fiber Optic Technician',
                    'slug' => 'network-fiber-optic-technician-telkom-akses',
                    'position' => 'Network & Fiber Optic Technician',
                    'description' => 'Instalasi jaringan kabel fiber optik, pemeliharaan router, penyambungan Splicing, dan penanganan gangguan jaringan.',
                    'qualification' => "1. Siswa kelas 12 atau Alumni SMK jurusan TKJ / RPL\n2. Memahami dasar jaringan komputer dan fiber optik\n3. Siap bekerja di lapangan",
                    'quota' => 10,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Malang Raya',
                    'min_salary' => 4500000,
                    'max_salary' => 5800000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKJ', 'RPL'],
                ],
                [
                    'company_id' => $unitedTractors->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior Heavy Equipment Mechanic',
                    'slug' => 'junior-heavy-equipment-mechanic-united-tractors',
                    'position' => 'Junior Heavy Equipment Mechanic',
                    'description' => 'Perawatan berkala dan perbaikan unit alat berat (excavator/dozer), analisa sistem hidrolik & mesin diesel.',
                    'qualification' => "1. Alumni SMK jurusan TKR / TKJ / Mesin\n2. Disiplin tinggi dan taat SOP K3\n3. Penempatan Surabaya",
                    'quota' => 8,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Surabaya',
                    'min_salary' => 5800000,
                    'max_salary' => 7500000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKJ', 'TKR'],
                ],
                [
                    'company_id' => $astra->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetClass12?->id,
                    'title' => 'Lowongan QC Line Inspector',
                    'slug' => 'qc-line-inspector-astra-honda',
                    'position' => 'Quality Control Line Inspector',
                    'description' => 'Melakukan inspeksi kelayakan fisik dan uji fungsi komponen presisi kendaraan sepeda motor di jalur perakitan.',
                    'qualification' => "1. Siswa kelas 12 jurusan TKR / TSM / TP\n2. Teliti, cekatan, dan tidak buta warna\n3. Penempatan pabrik Karawang",
                    'quota' => 15,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Karawang, Jabar',
                    'min_salary' => 5200000,
                    'max_salary' => 6200000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKR', 'TSM', 'TP'],
                ],
                [
                    'company_id' => $telkom->id,
                    'job_type_id' => $jobTypeContract?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetBoth?->id,
                    'title' => 'Lowongan NOC Junior Support',
                    'slug' => 'noc-junior-support-telkom-akses',
                    'position' => 'NOC Junior Support',
                    'description' => 'Monitoring traffic data pelanggan, eskalasi tiket gangguan internet, dan verifikasi konfigurasi switch / OLT.',
                    'qualification' => "1. Siswa kelas 12 atau Alumni TKJ / RPL\n2. Paham subnetting, routing dasar, dan Mikrotik\n3. Penempatan Malang",
                    'quota' => 6,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Malang Raya',
                    'min_salary' => 4200000,
                    'max_salary' => 5200000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKJ', 'RPL'],
                ],
                [
                    'company_id' => $unitedTractors->id,
                    'job_type_id' => $jobTypeInternship?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetClass12?->id,
                    'title' => 'Lowongan Magang Teknisi Alat Berat',
                    'slug' => 'magang-teknisi-alat-berat-ut',
                    'position' => 'Magang Teknisi Alat Berat',
                    'description' => 'Program praktik kerja industri di workshop alat berat, asistensi overhaul transmisi dan hydraulic piping.',
                    'qualification' => "1. Siswa SMK kelas 12 aktif jurusan TKR / TP\n2. Mendapat surat rekomendasi sekolah\n3. Lokasi Surabaya",
                    'quota' => 12,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Surabaya',
                    'min_salary' => 2500000,
                    'max_salary' => 3500000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['TKR', 'TP'],
                ],
                [
                    'company_id' => $teknoMaju->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior Frontend Developer',
                    'slug' => 'junior-frontend-developer-tekno-maju',
                    'position' => 'Junior Frontend Developer',
                    'description' => 'Mengembangkan antarmuka web responsif berbasis React, TypeScript, dan Tailwind CSS sesuai standar desain UI/UX modern.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Menguasai HTML, CSS, JavaScript ES6+, dan dasar framework React\n3. Mampu bekerja sama dalam tim agile dan menggunakan Git",
                    'quota' => 5,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Jakarta Selatan',
                    'min_salary' => 5500000,
                    'max_salary' => 7000000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $teknoMaju->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior Backend Developer',
                    'slug' => 'junior-backend-developer-tekno-maju',
                    'position' => 'Junior Backend Developer',
                    'description' => 'Membangun RESTful API performa tinggi menggunakan PHP Laravel, PostgreSQL, integrasi autentikasi JWT/Sanctum, serta unit testing.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Memahami konsep OOP, MVC, REST API, dan query database relational\n3. Terbiasa menggunakan Laravel dan relational database",
                    'quota' => 4,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Jakarta Selatan',
                    'min_salary' => 5500000,
                    'max_salary' => 7200000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $multimediaKreatif->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior Mobile App Developer',
                    'slug' => 'junior-mobile-app-developer-multimedia',
                    'position' => 'Junior Mobile App Developer',
                    'description' => 'Membangun aplikasi mobile multi-platform menggunakan Flutter / Dart dengan integrasi REST API dan push notification.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Menguasai dasar bahasa pemrograman Dart dan framework Flutter\n3. Memiliki portofolio aplikasi mobile sederhana",
                    'quota' => 3,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Jakarta Selatan',
                    'min_salary' => 5000000,
                    'max_salary' => 6800000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $multimediaKreatif->id,
                    'job_type_id' => $jobTypeContract?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Web Slicing & UI Implementer',
                    'slug' => 'web-slicing-ui-implementer-multimedia',
                    'position' => 'Web Slicing & UI Implementer',
                    'description' => 'Mengonversi desain Figma menjadi kode web pixel-perfect interaktif yang responsif di berbagai ukuran layar gawai.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Ahli dalam CSS3, Tailwind CSS, flexbox, grid, dan semantic HTML5\n3. Teliti terhadap kesesuaian detail visual desain",
                    'quota' => 4,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Jakarta Selatan',
                    'min_salary' => 4500000,
                    'max_salary' => 6000000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $telkom->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Software QA Tester',
                    'slug' => 'software-qa-tester-telkom-akses',
                    'position' => 'Software QA Tester',
                    'description' => 'Menyusun test case fungsional, melakukan manual dan automation API testing, serta mencatat bug report sistem internal perusahaan.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Memahami alur pengujian perangkat lunak dan penggunaan Postman\n3. Mampu menyusun dokumentasi skenario pengujian dengan rapi",
                    'quota' => 5,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Malang Raya',
                    'min_salary' => 4800000,
                    'max_salary' => 6200000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $telkom->id,
                    'job_type_id' => $jobTypeContract?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Fullstack Web Engineer',
                    'slug' => 'fullstack-web-engineer-telkom-akses',
                    'position' => 'Fullstack Web Engineer',
                    'description' => 'Pengembangan modul sistem monitoring jaringan operasional, dashboard analitik data pelanggan, dan pelaporan otomatis.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Berpengalaman dengan stack web PHP/Node.js dan database SQL\n3. Memahami konsep version control Git dan integrasi API",
                    'quota' => 4,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Malang Raya',
                    'min_salary' => 5200000,
                    'max_salary' => 6800000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $astra->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan IT Database & API Specialist',
                    'slug' => 'it-database-api-specialist-astra',
                    'position' => 'IT Database & API Specialist',
                    'description' => 'Mengelola optimasi database MySQL/PostgreSQL pabrik, otomatisasi sinkronisasi data antar modul ERP, dan pembuatan endpoint integrasi.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Menguasai DDL/DML SQL, indexing, stored procedure, dan normalisasi database\n3. Memiliki pemahaman query optimization dan backup berkala",
                    'quota' => 3,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Karawang, Jabar',
                    'min_salary' => 6000000,
                    'max_salary' => 7500000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $astra->id,
                    'job_type_id' => $jobTypeContract?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior IT Application Support',
                    'slug' => 'junior-it-application-support-astra',
                    'position' => 'Junior IT Application Support',
                    'description' => 'Penanganan keluhan sistem perangkat lunak operasional perakitan, troubleshooting bug aplikasi web, dan pelatihan user pabrik.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Memiliki kemampuan problem solving logika pemrograman yang baik\n3. Komunikatif dan siap bertugas di lingkungan manufaktur",
                    'quota' => 4,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Karawang, Jabar',
                    'min_salary' => 5300000,
                    'max_salary' => 6500000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $unitedTractors->id,
                    'job_type_id' => $jobTypeFullTime?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan ERP Internal Tool Developer',
                    'slug' => 'erp-internal-tool-developer-united-tractors',
                    'position' => 'ERP Internal Tool Developer',
                    'description' => 'Mengembangkan modul penunjang inventory logistik sparepart alat berat, pencatatan otomatisasi servis, dan reporting dashboard.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Memahami alur proses bisnis inventaris dan database SQL\n3. Mampu membuat antarmuka form interaktif dan integrasi CRUD",
                    'quota' => 3,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Surabaya',
                    'min_salary' => 5800000,
                    'max_salary' => 7300000,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
                [
                    'company_id' => $unitedTractors->id,
                    'job_type_id' => $jobTypeContract?->id,
                    'status_id' => $statusPublished?->id,
                    'target_applicant_id' => $targetAlumni?->id,
                    'title' => 'Lowongan Junior Cloud & DevOps Assistant',
                    'slug' => 'junior-cloud-devops-assistant-united-tractors',
                    'position' => 'Junior Cloud & DevOps Assistant',
                    'description' => 'Mendukung deployment sistem aplikasi ke server Linux/Docker, monitoring uptime container, dan otomasi backup data harian.',
                    'qualification' => "1. Khusus Alumni SMK jurusan Rekayasa Perangkat Lunak (RPL)\n2. Mengenal dasar Linux command line, Docker container, dan Web Server Nginx/Apache\n3. Teliti, disiplin, dan memiliki minat tinggi di infrastruktur cloud",
                    'quota' => 3,
                    'deadline' => '2026-12-31',
                    'work_location' => 'Surabaya',
                    'min_salary' => 5600000,
                    'max_salary' => 7000000,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'major_codes' => ['RPL'],
                ],
            ];

            foreach ($vacanciesData as $item) {
                $majorCodes = $item['major_codes'];
                unset($item['major_codes']);

                $vacancy = JobVacancy::updateOrCreate(
                    ['slug' => $item['slug']],
                    $item
                );

                $majorIds = [];
                foreach ($majorCodes as $code) {
                    if ($majorMap->has($code)) {
                        $majorIds[] = $majorMap->get($code)->id;
                    }
                }

                if (! empty($majorIds)) {
                    $vacancy->majors()->sync($majorIds);
                }
            }

            JobVacancy::where('is_active', true)
                ->where('deadline', '<', now()->toDateString())
                ->update(['deadline' => '2026-12-31']);
        });
    }
}
