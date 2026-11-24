<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_o2c_database_tables_are_created_successfully(): void
    {
        $tables = [
            'companies',
            'users',
            'roles',
            'customers',
            'products',
            'warehouses',
            'stock_balances',
            'sales_orders',
            'sales_order_items',
            'shipments',
            'invoices',
            'invoice_items',
            'payments',
            'payment_allocations',
            'sales_returns',
            'credit_notes',
            'customer_ledger_entries',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "The {$table} table should exist."
            );
        }
    }
}