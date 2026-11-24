<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_credit_reviews', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->unsignedBigInteger('reviewed_by')->nullable()->change();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_credit_reviews', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->unsignedBigInteger('reviewed_by')->nullable(false)->change();

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};