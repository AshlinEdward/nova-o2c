<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Company;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $gujarat = Company::where('code', 'NOVA-GJ')->firstOrFail();
        $delhi = Company::where('code', 'NOVA-DL')->firstOrFail();

        $warehouses = [
            [
                'company_id' => $gujarat->id,
                'code' => 'WH-AMD',
                'name' => 'Ahmedabad Warehouse',
                'location' => 'Ahmedabad',
            ],
            [
                'company_id' => $gujarat->id,
                'code' => 'WH-SRT',
                'name' => 'Surat Warehouse',
                'location' => 'Surat',
            ],
            [
                'company_id' => $delhi->id,
                'code' => 'WH-DEL',
                'name' => 'Delhi Warehouse',
                'location' => 'Delhi',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                [
                    'company_id' => $warehouse['company_id'],
                    'code' => $warehouse['code'],
                ],
                $warehouse
            );
        }
    }
}
