<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\PriceSlab;
use App\Models\Promotion;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'NOVA-GJ')->firstOrFail();

        // Create the GOLD customer price list.
        $gold = PriceList::updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'GOLD',
            ],
            [
                'name' => 'Gold Customer Price List',
                'is_active' => true,
            ]
        );

        // Assign GOLD pricing and preferred warehouses.
        $amd = Warehouse::where('company_id', $company->id)
            ->where('code', 'WH-AMD')->firstOrFail();

        $srt = Warehouse::where('company_id', $company->id)
            ->where('code', 'WH-SRT')->firstOrFail();

        Customer::where('company_id', $company->id)
            ->where('code', 'C001')
            ->update([
                'price_list_id' => $gold->id,
                'preferred_warehouse_id' => $amd->id,
            ]);

        Customer::where('company_id', $company->id)
            ->where('code', 'C003')
            ->update([
                'price_list_id' => $gold->id,
                'preferred_warehouse_id' => $srt->id,
            ]);

        // GOLD prices.
        foreach ([
            'P001' => 92,
            'P003' => 110,
        ] as $code => $price) {
            $product = Product::where('company_id', $company->id)
                ->where('code', $code)->firstOrFail();

            PriceListItem::updateOrCreate(
                [
                    'price_list_id' => $gold->id,
                    'product_id' => $product->id,
                ],
                ['unit_price' => $price]
            );
        }

        // Quantity-based discount slabs.
        $slabs = [
            ['P001', 50, 3],
            ['P001', 100, 5],
            ['P002', 20, 2],
        ];

        foreach ($slabs as [$code, $minimum, $discount]) {
            $product = Product::where('company_id', $company->id)
                ->where('code', $code)->firstOrFail();

            PriceSlab::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'minimum_quantity' => $minimum,
                ],
                [
                    'discount_percent' => $discount,
                    'is_active' => true,
                ]
            );
        }

        // Promotional offers.
        $promotions = [
            [
                'product' => 'P002',
                'code' => 'WINTER10',
                'name' => 'Winter 10 Percent Discount',
                'type' => 'PERCENTAGE',
                'discount' => 10,
                'buy' => 0,
                'free' => 0,
                'start' => '2026-11-01',
                'end' => '2027-03-31',
            ],
            [
                'product' => 'P002',
                'code' => 'MONSOON15',
                'name' => 'Monsoon 15 Percent Discount',
                'type' => 'PERCENTAGE',
                'discount' => 15,
                'buy' => 0,
                'free' => 0,
                'start' => '2026-07-01',
                'end' => '2026-08-31',
            ],
            [
                'product' => 'P003',
                'code' => 'B10G1',
                'name' => 'Buy 10 Get 1 Free',
                'type' => 'BUY_X_GET_Y',
                'discount' => 0,
                'buy' => 10,
                'free' => 1,
                'start' => '2026-11-01',
                'end' => '2027-03-31',
            ],
        ];

        foreach ($promotions as $data) {
            $product = Product::where('company_id', $company->id)
                ->where('code', $data['product'])->firstOrFail();

            Promotion::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $data['code'],
                ],
                [
                    'product_id' => $product->id,
                    'name' => $data['name'],
                    'promotion_type' => $data['type'],
                    'discount_percent' => $data['discount'],
                    'buy_quantity' => $data['buy'],
                    'free_quantity' => $data['free'],
                    'starts_at' => $data['start'],
                    'ends_at' => $data['end'],
                    'is_active' => true,
                ]
            );
        }
    }
}
