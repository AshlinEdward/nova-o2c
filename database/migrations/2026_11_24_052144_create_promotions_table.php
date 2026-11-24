
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('code', 40);
            $table->string('name', 100);

            $table->string('promotion_type', 30);

            $table->decimal('discount_percent', 5, 2)->default(0);

            $table->decimal('buy_quantity', 15, 3)->default(0);
            $table->decimal('free_quantity', 15, 3)->default(0);

            $table->date('starts_at');
            $table->date('ends_at');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['company_id', 'code'],
                'promotion_company_code_unique'
            );

            $table->index([
                'company_id',
                'product_id',
                'is_active',
                'starts_at',
                'ends_at',
            ], 'promotion_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
