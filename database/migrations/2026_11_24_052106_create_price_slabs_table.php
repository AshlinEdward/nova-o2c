<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_slabs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->decimal('minimum_quantity', 15, 3);
            $table->decimal('discount_percent', 5, 2);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['company_id', 'product_id', 'minimum_quantity'],
                'price_slab_company_product_quantity_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_slabs');
    }
};
