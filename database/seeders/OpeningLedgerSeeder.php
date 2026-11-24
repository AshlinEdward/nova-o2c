<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use Illuminate\Database\Seeder;

class OpeningLedgerSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'NOVA-GJ')->firstOrFail();

        $invoices = [
            [
                'customer' => 'C001',
                'reference' => 1,
                'amount' => 45000,
                'date' => '2026-08-01',
                'due_date' => '2026-08-31',
            ],
            [
                'customer' => 'C002',
                'reference' => 2,
                'amount' => 70000,
                'date' => '2026-11-01',
                'due_date' => '2026-11-16',
            ],
        ];

        foreach ($invoices as $invoice) {
            $customer = Customer::where('company_id', $company->id)
                ->where('code', $invoice['customer'])
                ->firstOrFail();

            CustomerLedgerEntry::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'reference_type' => 'OPENING_INVOICE',
                    'reference_id' => $invoice['reference'],
                ],
                [
                    'entry_type' => 'INVOICE',
                    'amount' => $invoice['amount'],
                    'outstanding_amount' => $invoice['amount'],
                    'entry_date' => $invoice['date'],
                    'due_date' => $invoice['due_date'],
                    'description' => 'Opening unpaid invoice',
                ]
            );
        }
    }
}
