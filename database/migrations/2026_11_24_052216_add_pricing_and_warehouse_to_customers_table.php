
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('price_list_id')
                ->nullable()
                ->after('payment_terms_days')
                ->constrained('price_lists')
                ->restrictOnDelete();

            $table->foreignId('preferred_warehouse_id')
                ->nullable()
                ->after('price_list_id')
                ->constrained('warehouses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
            $table->dropConstrainedForeignId('preferred_warehouse_id');
        });
    }
};
