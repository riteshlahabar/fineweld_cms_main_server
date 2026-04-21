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
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'sale_order_id')) {
                $table->unsignedBigInteger('sale_order_id')->nullable()->after('quotation_status');
                $table->foreign('sale_order_id')->references('id')->on('sale_orders');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'sale_order_id')) {
                $table->dropForeign(['sale_order_id']);
                $table->dropColumn('sale_order_id');
            }
        });
    }
};

