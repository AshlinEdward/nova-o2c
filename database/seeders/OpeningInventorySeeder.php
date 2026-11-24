<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class OpeningInventorySeeder extends Seeder
{
    public function run(InventoryService $inventory): void
    {
        $openingStock = [
            ['NOVA-GJ', 'WH-AMD', 'P001', 100, 50],
            ['NOVA-GJ', 'WH-AMD', 'P001', 100, 55],
            ['NOVA-GJ', 'WH-AMD', 'P002', 200, 380],
            ['NOVA-GJ', 'WH-AMD', 'P003', 500, 80],

            ['NOVA-GJ', 'WH-SRT', 'P001', 50, 52],
            ['NOVA-GJ', 'WH-SRT', 'P003', 100, 82],
            ['NOVA-GJ', 'WH-SRT', 'P005', 10, 150],

            ['NOVA-DL', 'WH-DEL', 'P001', 300, 51],
        ];

        foreach ($openingStock as $index => $row) {
            [$companyCode, $warehouseCode, $productCode, $qty, $cost] = $row;

            $company = Company::where('code', $companyCode)->firstOrFail();

            $warehouse = Warehouse::where('company_id', $company->id)
                ->where('code', $warehouseCode)
                ->firstOrFail();

            $product = Product::where('company_id', $company->id)
                ->where('code', $productCode)
                ->firstOrFail();

            $reference = "OPENING-STOCK-{$index}";

            // Prevent duplicate opening stock when the seeder is rerun.
            $alreadySeeded = \App\Models\InventoryMovement::where(
                'notes',
                $reference
            )->exists();

            if ($alreadySeeded) {
                continue;
            }

            $inventory->receiveStock(
                $company->id,
                $warehouse->id,
                $product->id,
                $qty,
                $cost,
                null,
                $reference
            );
        }
    }
}
