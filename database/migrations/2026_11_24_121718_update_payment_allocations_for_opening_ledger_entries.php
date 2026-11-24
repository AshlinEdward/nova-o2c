
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreignId('invoice_id')
                ->nullable()
                ->change();

            $table->foreignId('ledger_entry_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('customer_ledger_entries')
                ->restrictOnDelete();

            $table->unique(
                ['payment_id', 'ledger_entry_id'],
                'payment_ledger_entry_unique'
            );

            $table->index('ledger_entry_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropUnique('payment_ledger_entry_unique');
            $table->dropIndex(['ledger_entry_id']);
            $table->dropForeign(['ledger_entry_id']);
            $table->dropColumn('ledger_entry_id');

            $table->foreignId('invoice_id')
                ->nullable(false)
                ->change();
        });
    }
};
