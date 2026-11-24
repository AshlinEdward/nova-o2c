<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('sales_order_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('shipment_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->string('invoice_number', 60);
            $table->string('financial_year', 10);

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->decimal('cogs_amount', 15, 2)->default(0);
            $table->decimal('gross_margin', 15, 2)->default(0);

            $table->string('status', 20)->default('POSTED');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['company_id', 'invoice_number'],
                'invoices_company_number_unique'
            );

            $table->index(['company_id', 'financial_year']);
            $table->index(['customer_id', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
