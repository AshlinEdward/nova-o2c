
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('sales_order_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 3);

            $table->timestamps();

            $table->unique([
                'shipment_id',
                'sales_order_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
