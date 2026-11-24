<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')
                ->constrained('sales_returns')
                ->cascadeOnDelete();

            $table->foreignId('invoice_item_id')
                ->constrained('invoice_items')
                ->restrictOnDelete();

            $table->foreignId('sales_order_item_id')
                ->constrained('sales_order_items')
                ->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('taxable_amount', 15, 2);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->decimal('cogs_reversed', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['sales_return_id', 'invoice_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
    }
};
