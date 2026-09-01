<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Company;
use App\Models\StandardType;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $companies = [
            [
                'name' => 'PT Teknologi Maju Indonesia',
                'industry_code' => 'software_house_it',
                'address' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan, DKI Jakarta 12190',
                'email' => 'hrd@teknologimaju.co.id',
                'phone' => '081234567890',
                'website' => 'https://www.teknologimaju.co.id',
                'pic_name' => 'Budi Santoso',
                'pic_contact' => '081298765432',
                'is_active' => true,
            ],
            [
                'name' => 'PT Otomotif Nusantara',
                'industry_code' => 'manufacturing_automotive',
                'address' => 'Kawasan Industri Pulo Gadung, Jakarta Timur, DKI Jakarta 13260',
                'email' => 'recruitment@otomotifnusantara.com',
                'phone' => '085611122233',
                'website' => 'https://www.otomotifnusantara.com',
                'pic_name' => 'Siti Rahayu',
                'pic_contact' => '085699988877',
                'is_active' => true,
            ],
            [
                'name' => 'PT Elektronik Cemerlang',
                'industry_code' => 'electronics_hardware',
                'address' => 'Jl. Raya Serpong KM 7, Tangerang Selatan, Banten 15310',
                'email' => 'career@elektronikcemerlang.co.id',
                'phone' => '082122233344',
                'website' => 'https://www.elektronikcemerlang.co.id',
                'pic_name' => 'Andi Wijaya',
                'pic_contact' => '082155566677',
                'is_active' => true,
            ],
            [
                'name' => 'PT Telekomunikasi Prima',
                'industry_code' => 'telecommunication_networking',
                'address' => 'Menara Prima, Jl. Rasuna Said, Jakarta Selatan, DKI Jakarta 12940',
                'email' => 'hiring@telekomprima.net',
                'phone' => '083122244455',
                'website' => 'https://www.telekomprima.net',
                'pic_name' => 'Dewi Lestari',
                'pic_contact' => '083188877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Multinedia Kreatif',
                'industry_code' => 'multimedia_creative_agency',
                'address' => 'Jl. Gatot Subroto No. 100, Jakarta Selatan, DKI Jakarta 12870',
                'email' => 'kerja@multimedia-kreatif.id',
                'phone' => '084422233344',
                'website' => 'https://www.multimedia-kreatif.id',
                'pic_name' => 'Rina Marlina',
                'pic_contact' => '084488877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Mesin Berat Indonesia',
                'industry_code' => 'machinery_heavy_equipment',
                'address' => 'Kawasan Industri Cikarang, Kab. Bekasi, Jawa Barat 17550',
                'email' => 'talent@mesinberat.co.id',
                'phone' => '085533344455',
                'website' => 'https://www.mesinberat.co.id',
                'pic_name' => 'Eko Pratama',
                'pic_contact' => '085599988877',
                'is_active' => true,
            ],
            [
                'name' => 'PT Konstruksi Jaya Sakti',
                'industry_code' => 'construction_property',
                'address' => 'Jl. Letjen S. Parman Kav. 28, Jakarta Barat, DKI Jakarta 11410',
                'email' => 'career@konstruksijaya.co.id',
                'phone' => '086622233344',
                'website' => 'https://www.konstruksijaya.co.id',
                'pic_name' => 'Fajar Nugroho',
                'pic_contact' => '086688877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Retail Sejahtera',
                'industry_code' => 'retail_fnb_hospitality',
                'address' => 'Jl. Boulevard Raya Blok QA, Jakarta Utara, DKI Jakarta 14240',
                'email' => 'hiring@retailsejahtera.co.id',
                'phone' => '087722233344',
                'website' => 'https://www.retailsejahtera.co.id',
                'pic_name' => 'Galuh Ayu',
                'pic_contact' => '087788877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Bank Nusantara Sejahtera',
                'industry_code' => 'finance_banking',
                'address' => 'Gedung Bank Nusantara, Jl. MH Thamrin No. 5, Jakarta Pusat, DKI Jakarta 10340',
                'email' => 'recruitment@banknusantara.co.id',
                'phone' => '088822233344',
                'website' => 'https://www.banknusantara.co.id',
                'pic_name' => 'Hendra Gunawan',
                'pic_contact' => '088888877766',
                'is_active' => true,
            ],
            [
                'name' => 'PT Agro Mandiri Perkasa',
                'industry_code' => 'other',
                'address' => 'Jl. Raya Bogor KM 20, Kec. Cimanggis, Depok, Jawa Barat 16952',
                'email' => 'career@agromandiri.co.id',
                'phone' => '089922233344',
                'website' => 'https://www.agromandiri.co.id',
                'pic_name' => 'Indah Permata',
                'pic_contact' => '089988877766',
                'is_active' => true,
            ],
        ];

        foreach ($companies as $company) {
            $industry = StandardType::byCategory('company_industry')
                ->where('code', $company['industry_code'])
                ->first();

            Company::firstOrCreate(
                ['email' => $company['email']],
                [
                    'user_id' => $admin?->id,
                    'industry_id' => $industry?->id,
                    'name' => $company['name'],
                    'address' => $company['address'],
                    'phone' => $company['phone'],
                    'website' => $company['website'],
                    'pic_name' => $company['pic_name'],
                    'pic_contact' => $company['pic_contact'],
                    'is_active' => $company['is_active'],
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
