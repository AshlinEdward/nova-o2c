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
        Schema::create('sales_order_item_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('reserved_quantity', 15, 3)->default(0);
            $table->decimal('shipped_quantity', 15, 3)->default(0);
            $table->decimal('backorder_quantity', 15, 3)->default(0);

            $table->timestamps();

            $table->unique(
                ['sales_order_item_id', 'warehouse_id'],
                'order_item_warehouse_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_item_allocations');
    }
};
