<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\StandardType;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $hrd = User::where('role', 'hrd')->where('email', 'hrd@email.com')->first();

        $companies = [
            [
                'name' => 'PT Kejayaan Terraloka',
                'industry_code' => 'construction_property',
                'address' => 'Jl. Mayjen Sungkono No. 88, Dukuh Pakis, Surabaya, Jawa Timur 60225',
                'email' => 'hrd@kejayaan-terraloka.com',
                'phone' => '031-5678901',
                'website' => 'https://www.kejayaan-terraloka.com',
                'pic_name' => 'Hendro Prasetyo, S.T.',
                'pic_contact' => '081234567890',
                'is_active' => true,
                'user_id' => $hrd?->id,
            ],
            [
                'name' => 'PT Hyperdata Solusindo Mandiri',
                'industry_code' => 'software_house_it',
                'address' => 'Jl. Danau Ranau Raya No. G5/A11, Sawojajar, Kota Malang, Jawa Timur 65139',
                'email' => 'karir@hyperdata.co.id',
                'phone' => '0341-712345',
                'website' => 'https://www.hyperdata.co.id',
                'pic_name' => 'Ahmad Zulkarnain, S.Kom.',
                'pic_contact' => '081234567891',
                'is_active' => true,
            ],
            [
                'name' => 'PT Bank Central Asia',
                'industry_code' => 'finance_banking',
                'address' => 'Menara BCA Grand Indonesia, Jl. M.H. Thamrin No. 1, Jakarta Pusat, DKI Jakarta 10310',
                'email' => 'recruitment@bca.co.id',
                'phone' => '021-23588000',
                'website' => 'https://www.bca.co.id',
                'pic_name' => 'Grace Natalie, S.E.',
                'pic_contact' => '081234567892',
                'is_active' => true,
            ],
            [
                'name' => 'PT Perusahaan Listrik Negara',
                'industry_code' => 'other',
                'address' => 'Jl. Trunojoyo Blok M-I No. 135, Kebayoran Baru, Jakarta Selatan, DKI Jakarta 12160',
                'email' => 'rekrutmen@pln.co.id',
                'phone' => '021-7261122',
                'website' => 'https://web.pln.co.id',
                'pic_name' => 'Ir. Bambang Sugiarto',
                'pic_contact' => '081234567893',
                'is_active' => true,
            ],
            [
                'name' => 'PT Telkom Indonesia',
                'industry_code' => 'telecommunication_networking',
                'address' => 'Telkom Landmark Tower, Jl. Jend. Gatot Subroto Kav. 52, Jakarta Selatan, DKI Jakarta 12710',
                'email' => 'careers@telkom.co.id',
                'phone' => '021-5215100',
                'website' => 'https://www.telkom.co.id',
                'pic_name' => 'Rahmat Hidayat, M.T.',
                'pic_contact' => '081234567894',
                'is_active' => true,
            ],
            [
                'name' => 'PT Astra Honda Motor',
                'industry_code' => 'manufacturing_automotive',
                'address' => 'Jl. Laksda Yos Sudarso, Sunter I, Jakarta Utara, DKI Jakarta 14350',
                'email' => 'recruitment@astra-honda.com',
                'phone' => '021-6518080',
                'website' => 'https://www.astra-honda.com',
                'pic_name' => 'Budi Santoso, S.T.',
                'pic_contact' => '081234567895',
                'is_active' => true,
            ],
            [
                'name' => 'PT United Tractors Tbk',
                'industry_code' => 'machinery_heavy_equipment',
                'address' => 'Jl. Raya Bekasi KM 22, Cakung, Jakarta Timur, DKI Jakarta 13910',
                'email' => 'career@unitedtractors.com',
                'phone' => '021-24579999',
                'website' => 'https://www.unitedtractors.com',
                'pic_name' => 'Dedi Kurniawan, S.T.',
                'pic_contact' => '081234567896',
                'is_active' => true,
            ],
            [
                'name' => 'PT Teknologi Maju Indonesia',
                'industry_code' => 'software_house_it',
                'address' => 'Cyber 2 Tower Lantai 18, Jl. HR Rasuna Said Blok X-5 No. 13, Jakarta Selatan, DKI Jakarta 12950',
                'email' => 'hrd@teknologimaju.co.id',
                'phone' => '021-29021234',
                'website' => 'https://www.teknologimaju.co.id',
                'pic_name' => 'Faisal Pratama, S.Kom.',
                'pic_contact' => '081298765432',
                'is_active' => true,
            ],
            [
                'name' => 'PT Multimedia Kreatif',
                'industry_code' => 'multimedia_creative_agency',
                'address' => 'Graha Pena Lantai 8, Jl. Ahmad Yani No. 88, Surabaya, Jawa Timur 60234',
                'email' => 'kerja@multimedia-kreatif.id',
                'phone' => '031-8283344',
                'website' => 'https://www.multimedia-kreatif.id',
                'pic_name' => 'Rina Marlina, S.Ds.',
                'pic_contact' => '084488877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Otomotif Nusantara',
                'industry_code' => 'manufacturing_automotive',
                'address' => 'Kawasan Industri PIER, Jl. Rembang Industri Raya No. 45, Pasuruan, Jawa Timur 67152',
                'email' => 'recruitment@otomotifnusantara.com',
                'phone' => '0343-740111',
                'website' => 'https://www.otomotifnusantara.com',
                'pic_name' => 'Siti Rahayu, S.Psi.',
                'pic_contact' => '085699988877',
                'is_active' => true,
            ],
            [
                'name' => 'PT Telekomunikasi Prima',
                'industry_code' => 'telecommunication_networking',
                'address' => 'Menara Prima Lantai 12, Jl. DR. Ide Anak Agung Gde Agung Kav. 6.2, Jakarta Selatan, DKI Jakarta 12950',
                'email' => 'hiring@telekomprima.net',
                'phone' => '021-57948888',
                'website' => 'https://www.telekomprima.net',
                'pic_name' => 'Dewi Lestari, S.T.',
                'pic_contact' => '083188877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Mesin Berat Indonesia',
                'industry_code' => 'machinery_heavy_equipment',
                'address' => 'Kawasan Industri SIER, Jl. Rungkut Industri IV No. 12, Surabaya, Jawa Timur 60293',
                'email' => 'talent@mesinberat.co.id',
                'phone' => '031-8432255',
                'website' => 'https://www.mesinberat.co.id',
                'pic_name' => 'Eko Pratama, S.T.',
                'pic_contact' => '085599988877',
                'is_active' => true,
            ],
            [
                'name' => 'PT Petrokimia Gresik',
                'industry_code' => 'other',
                'address' => 'Jl. Jenderal Ahmad Yani, Karangturi, Gresik, Jawa Timur 61119',
                'email' => 'recruitment@petrokimia-gresik.com',
                'phone' => '031-3981811',
                'website' => 'https://petrokimia-gresik.com',
                'pic_name' => 'Haris Munandar, S.T.',
                'pic_contact' => '081333444555',
                'is_active' => true,
            ],
            [
                'name' => 'PT Freeport Indonesia',
                'industry_code' => 'machinery_heavy_equipment',
                'address' => 'Plaza 89 Lantai 5, Jl. H.R. Rasuna Said Kav. X-7 No. 6, Jakarta Selatan, DKI Jakarta 12940',
                'email' => 'careers@fmi.co.id',
                'phone' => '021-2591818',
                'website' => 'https://ptfi.co.id',
                'pic_name' => 'Yohanes Wibisono, S.T.',
                'pic_contact' => '081122334455',
                'is_active' => true,
            ],
            [
                'name' => 'PT Elektronik Cemerlang',
                'industry_code' => 'electronics_hardware',
                'address' => 'Kawasan Industri Jababeka Cikarang, Blok C No. 10, Cikarang Utara, Bekasi, Jawa Barat 17530',
                'email' => 'career@elektronikcemerlang.co.id',
                'phone' => '021-8934455',
                'website' => 'https://www.elektronikcemerlang.co.id',
                'pic_name' => 'Andi Wijaya, S.T.',
                'pic_contact' => '082155566677',
                'is_active' => true,
            ],
            [
                'name' => 'PT Konstruksi Jaya Sakti',
                'industry_code' => 'construction_property',
                'address' => 'Wisma Jaya Sakti Lantai 5, Jl. Letjen S. Parman Kav. 28, Jakarta Barat, DKI Jakarta 11410',
                'email' => 'career@konstruksijaya.co.id',
                'phone' => '021-53662233',
                'website' => 'https://www.konstruksijaya.co.id',
                'pic_name' => 'Fajar Nugroho, S.T.',
                'pic_contact' => '086688877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Retail Sejahtera',
                'industry_code' => 'retail_fnb_hospitality',
                'address' => 'Sentra Retail Maluku, Jl. Boulevard Raya Blok QA No. 1-3, Kelapa Gading, Jakarta Utara, DKI Jakarta 14240',
                'email' => 'hiring@retailsejahtera.co.id',
                'phone' => '021-45851234',
                'website' => 'https://www.retailsejahtera.co.id',
                'pic_name' => 'Galuh Ayu, S.E.',
                'pic_contact' => '087788877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Bank Nusantara Sejahtera',
                'industry_code' => 'finance_banking',
                'address' => 'Gedung Bank Nusantara, Jl. MH Thamrin No. 5, Jakarta Pusat, DKI Jakarta 10340',
                'email' => 'recruitment@banknusantara.co.id',
                'phone' => '021-39832244',
                'website' => 'https://www.banknusantara.co.id',
                'pic_name' => 'Hendra Gunawan, S.E., M.M.',
                'pic_contact' => '088888877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Indomobil Sukses Internasional',
                'industry_code' => 'manufacturing_automotive',
                'address' => 'Wisma Indomobil I Lantai 6, Jl. Letjen M.T. Haryono Kav. 8, Jakarta Timur, DKI Jakarta 13330',
                'email' => 'recruitment@indomobil.co.id',
                'phone' => '021-8564811',
                'website' => 'https://www.indomobil.com',
                'pic_name' => 'Rudy Hartono, S.E.',
                'pic_contact' => '081299887766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Shopee International Indonesia',
                'industry_code' => 'software_house_it',
                'address' => 'Pacific Century Place Lantai 26, SCBD Lot 10, Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan, DKI Jakarta 12190',
                'email' => 'recruitment@shopee.co.id',
                'phone' => '021-80647100',
                'website' => 'https://careers.shopee.co.id',
                'pic_name' => 'Clarissa Putri, S.I.Kom.',
                'pic_contact' => '081388776655',
                'is_active' => true,
            ],
        ];

        foreach ($companies as $company) {
            $industry = StandardType::byCategory('company_industry')
                ->where('code', $company['industry_code'])
                ->first();

            $userId = $company['user_id'] ?? $admin?->id;

            Company::firstOrCreate(
                ['email' => $company['email']],
                [
                    'user_id' => $userId,
                    'industry_id' => $industry?->id,
                    'name' => $company['name'],
                    'address' => $company['address'],
                    'phone' => $company['phone'],
                    'website' => $company['website'],
                    'pic_name' => $company['pic_name'],
                    'pic_contact' => $company['pic_contact'],
                    'is_active' => $company['is_active'],
                    'created_by' => $userId,
                ]
            );
        }
    }
}
