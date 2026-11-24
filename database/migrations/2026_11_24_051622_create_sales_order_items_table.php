<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // Product details frozen at order creation.
            $table->string('product_code', 20);
            $table->string('product_name');
            $table->string('hsn_code', 20);

            $table->decimal('quantity', 15, 3);

            // Free promotional goods are stored as separate zero-price lines.
            $table->boolean('is_free')->default(false);

            $table->decimal('unit_price', 15, 2)->default(0);

            $table->decimal('gross_amount', 15, 2)->default(0);

            $table->decimal('slab_discount_percent', 5, 2)->default(0);
            $table->decimal('slab_discount_amount', 15, 2)->default(0);

            $table->decimal('promo_discount_percent', 5, 2)->default(0);
            $table->decimal('promo_discount_amount', 15, 2)->default(0);

            $table->decimal('taxable_amount', 15, 2)->default(0);

            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            // Stores the pricing calculation details for audit purposes.
            $table->json('pricing_breakdown')->nullable();

            $table->timestamps();

            $table->index(['sales_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
