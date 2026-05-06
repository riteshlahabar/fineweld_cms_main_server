<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'purchase_ledger_name' => 'sales_ledger_name',
        'expense_ledger_name' => 'purchase_ledger_name',
        'cash_ledger_name' => 'expense_ledger_name',
        'bank_ledger_name' => 'cash_ledger_name',
        'round_off_ledger_name' => 'bank_ledger_name',
        'cgst_ledger_name' => 'round_off_ledger_name',
        'sgst_ledger_name' => 'cgst_ledger_name',
        'igst_ledger_name' => 'sgst_ledger_name',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return;
        }

        foreach ($this->columns as $column => $afterColumn) {
            if (Schema::hasColumn('tally_integration_settings', $column)) {
                continue;
            }

            Schema::table('tally_integration_settings', function (Blueprint $table) use ($column, $afterColumn) {
                $table->string($column)->nullable()->after($afterColumn);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return;
        }

        foreach (array_reverse(array_keys($this->columns)) as $column) {
            if (! Schema::hasColumn('tally_integration_settings', $column)) {
                continue;
            }

            Schema::table('tally_integration_settings', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
