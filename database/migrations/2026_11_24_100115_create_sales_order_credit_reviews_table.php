
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_credit_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->restrictOnDelete();

            $table->foreignId('reviewed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('action', 20); // RELEASED or REJECTED
            $table->text('comment');
            $table->timestamp('reviewed_at');

            $table->timestamps();

            $table->index(['sales_order_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_credit_reviews');
    }
};
