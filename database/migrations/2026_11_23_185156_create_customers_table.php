<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('code', 20);
            $table->string('name');
            $table->string('gstin', 15)->nullable();
            $table->string('state', 100);

            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
