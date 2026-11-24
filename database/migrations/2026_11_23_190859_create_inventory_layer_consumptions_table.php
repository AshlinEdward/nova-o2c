<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_layer_consumptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_cost_layer_id')
                ->constrained('inventory_cost_layers')
                ->restrictOnDelete();

            $table->foreignId('inventory_movement_id')
                ->constrained('inventory_movements')
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2);

            $table->timestamps();

            $table->unique(
                ['inventory_movement_id', 'inventory_cost_layer_id'],
                'layer_consumption_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_layer_consumptions');
    }
};