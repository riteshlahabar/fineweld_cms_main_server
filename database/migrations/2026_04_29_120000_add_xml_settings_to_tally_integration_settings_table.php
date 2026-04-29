<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return;
        }

        if (! Schema::hasColumn('tally_integration_settings', 'company_name')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->string('company_name')->nullable()->after('host');
            });
        }

        if (! Schema::hasColumn('tally_integration_settings', 'xml_port')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->unsignedInteger('xml_port')->nullable()->after('company_name');
            });
        }

        if (! Schema::hasColumn('tally_integration_settings', 'sales_ledger_name')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->string('sales_ledger_name')->nullable()->after('xml_port');
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

        if (Schema::hasColumn('tally_integration_settings', 'sales_ledger_name')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->dropColumn('sales_ledger_name');
            });
        }

        if (Schema::hasColumn('tally_integration_settings', 'xml_port')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->dropColumn('xml_port');
            });
        }

        if (Schema::hasColumn('tally_integration_settings', 'company_name')) {
            Schema::table('tally_integration_settings', function (Blueprint $table) {
                $table->dropColumn('company_name');
            });
        }
    }
};
