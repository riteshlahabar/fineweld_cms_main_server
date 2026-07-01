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
        Schema::table('sale_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_orders', 'shipping_charge')) {
                $table->decimal('shipping_charge', 20, 4)->default(0)->after('note');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'shipping_charge')) {
                $table->decimal('shipping_charge', 20, 4)->default(0)->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sale_orders', 'shipping_charge')) {
                $table->dropColumn('shipping_charge');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'shipping_charge')) {
                $table->dropColumn('shipping_charge');
            }
        });
    }
};