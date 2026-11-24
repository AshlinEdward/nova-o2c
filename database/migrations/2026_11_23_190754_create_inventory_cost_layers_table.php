<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('inventory_movement_id')
                ->unique()
                ->constrained('inventory_movements')
                ->restrictOnDelete();

            $table->decimal('initial_quantity', 15, 3);
            $table->decimal('remaining_quantity', 15, 3);

            $table->decimal('unit_cost', 15, 2);

            $table->dateTime('received_at');

            $table->timestamps();

            $table->index(
                ['warehouse_id', 'product_id', 'received_at'],
                'fifo_layer_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
