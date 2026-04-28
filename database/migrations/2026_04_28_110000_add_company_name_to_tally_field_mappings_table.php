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
        if (! Schema::hasTable('tally_field_mappings') || Schema::hasColumn('tally_field_mappings', 'company_name')) {
            return;
        }

        Schema::table('tally_field_mappings', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('tally_field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tally_field_mappings') || ! Schema::hasColumn('tally_field_mappings', 'company_name')) {
            return;
        }

        Schema::table('tally_field_mappings', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }
};
