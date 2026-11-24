<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Company;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $gujarat = Company::where('code', 'NOVA-GJ')->firstOrFail();
        $delhi = Company::where('code', 'NOVA-DL')->firstOrFail();

        $customers = [
            [
                'company_id' => $gujarat->id,
                'code' => 'C001',
                'name' => 'Patel Kirana Stores',
                'state' => 'Gujarat',
                'credit_limit' => 500000,
                'payment_terms_days' => 30,
            ],
            [
                'company_id' => $gujarat->id,
                'code' => 'C002',
                'name' => 'Shah Supermart',
                'state' => 'Gujarat',
                'credit_limit' => 100000,
                'payment_terms_days' => 15,
            ],
            [
                'company_id' => $gujarat->id,
                'code' => 'C003',
                'name' => 'Mumbai Fresh Mart',
                'state' => 'Maharashtra',
                'credit_limit' => 300000,
                'payment_terms_days' => 30,
            ],
            [
                'company_id' => $delhi->id,
                'code' => 'C004',
                'name' => 'Delhi Daily Needs',
                'state' => 'Delhi',
                'credit_limit' => 200000,
                'payment_terms_days' => 30,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                [
                    'company_id' => $customer['company_id'],
                    'code' => $customer['code'],
                ],
                $customer
            );
        }
    }
}
