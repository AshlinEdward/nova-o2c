
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
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

            $table->decimal('on_hand', 15, 3)->default(0);
            $table->decimal('reserved', 15, 3)->default(0);
            $table->decimal('available', 15, 3)->default(0);

            $table->timestamps();

            $table->unique(
                ['warehouse_id', 'product_id'],
                'stock_warehouse_product_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
