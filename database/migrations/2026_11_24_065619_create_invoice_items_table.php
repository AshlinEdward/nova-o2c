<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('shipment_item_id')
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('sales_order_item_id')
                ->constrained()
                ->restrictOnDelete();

            // Product snapshot
            $table->string('product_code', 20);
            $table->string('product_name');

            $table->decimal('quantity', 15, 3);
            $table->boolean('is_free')->default(false);

            // Price and GST breakdown
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            // FIFO cost and margin
            $table->decimal('cogs_amount', 15, 2)->default(0);
            $table->decimal('gross_margin', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'sales_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
