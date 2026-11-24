<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'code' => 'NOVA-GJ',
            'name' => 'Nova Gujarat Pvt Ltd',
            'gstin' => '24AAACN1234N1Z8',
            'state' => 'Gujarat',
            'invoice_prefix' => 'NGJ',
            'is_active' => true,
        ]);

        Company::create([
            'code' => 'NOVA-DL',
            'name' => 'Nova Delhi Pvt Ltd',
            'gstin' => '07AAACN1234N1Z2',
            'state' => 'Delhi',
            'invoice_prefix' => 'NDL',
            'is_active' => true,
        ]);
    }
}
