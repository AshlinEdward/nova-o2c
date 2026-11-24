<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('financial_year', 10);
            $table->unsignedBigInteger('last_number')->default(0);

            $table->timestamps();

            $table->unique(
                ['company_id', 'financial_year'],
                'invoice_sequences_company_fy_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
