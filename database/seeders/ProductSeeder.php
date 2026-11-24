<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Company;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $products = [
            [
                'code' => 'P001',
                'name' => 'Instant Noodles Carton',
                'hsn_code' => '1902',
                'gst_rate' => 18,
                'unit' => 'BOX',
                'base_price' => 100,
            ],
            [
                'code' => 'P002',
                'name' => 'Basmati Rice 5kg',
                'hsn_code' => '1006',
                'gst_rate' => 5,
                'unit' => 'PCS',
                'base_price' => 450,
            ],
            [
                'code' => 'P003',
                'name' => 'Detergent Powder 1kg',
                'hsn_code' => '3402',
                'gst_rate' => 18,
                'unit' => 'PCS',
                'base_price' => 120,
            ],
            [
                'code' => 'P004',
                'name' => 'Biscuits Family Pack',
                'hsn_code' => '1905',
                'gst_rate' => 18,
                'unit' => 'PCS',
                'base_price' => 60,
            ],
            [
                'code' => 'P005',
                'name' => 'Mustard Oil 1L',
                'hsn_code' => '1514',
                'gst_rate' => 5,
                'unit' => 'PCS',
                'base_price' => 180,
            ],
        ];

        foreach ($companies as $company) {
            foreach ($products as $product) {
                Product::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $product['code'],
                    ],
                    array_merge($product, [
                        'company_id' => $company->id,
                    ])
                );
            }
        }
    }
}
