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
        Schema::create('partner_profit_shares', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date'); // Sale date or Sale return date
            $table->foreignId('item_profit_id')->constrained('item_profits')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->foreignId('sale_return_id')->nullable()->constrained('sale_return')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('share_type');
            $table->decimal('share_value', 8, 2)->default(0); // 0-100
            $table->decimal('distributed_profit_amount', 20, 4)->default(0);
            $table->decimal('distributed_received_amount', 20, 4)->default(0); // Sale invoice payment received from customer
            $table->decimal('distributed_paid_amount', 20, 4)->default(0); // Sale return payment paid to customer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_profit_shares');
    }
};
