<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'PT Kosmetik Indonesia Jaya',
                'npwp' => '01.234.567.8-901.000',
                'address' => 'Jl. Industri Raya No. 123, Kawasan Industri Pulogadung, Jakarta Timur 13920',
                'phone' => '021-4601234',
                'email' => 'sales@kosmetikindo.co.id',
                'contact_person' => 'Ibu Sari',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-002',
                'name' => 'CV Sumber Cantik Sejahtera',
                'npwp' => '02.345.678.9-012.000',
                'address' => 'Jl. Raya Bekasi KM 28, Bekasi 17132',
                'phone' => '021-8801234',
                'email' => 'order@sumbercantik.com',
                'contact_person' => 'Pak Hendra',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-003',
                'name' => 'PT Global Beauty Distribution',
                'npwp' => '03.456.789.0-123.000',
                'address' => 'Jl. Mangga Dua Raya No. 45, Jakarta Utara 14430',
                'phone' => '021-6121234',
                'email' => 'procurement@globalbeauty.co.id',
                'contact_person' => 'Ms. Diana',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-004',
                'name' => 'PT Harum Wangi Indonesia',
                'npwp' => '04.567.890.1-234.000',
                'address' => 'Jl. Raya Cikupa No. 88, Tangerang 15710',
                'phone' => '021-5901234',
                'email' => 'sales@harumwangi.co.id',
                'contact_person' => 'Pak Bambang',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-005',
                'name' => 'PT Profesional Hair Care',
                'npwp' => '05.678.901.2-345.000',
                'address' => 'Jl. Industri Selatan IV Blok GG No. 7, JABABEKA, Cikarang 17530',
                'phone' => '021-89831234',
                'email' => 'order@prohaircare.co.id',
                'contact_person' => 'Ibu Linda',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }
}
