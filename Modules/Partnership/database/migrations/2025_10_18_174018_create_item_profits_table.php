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
        Schema::create('item_profits', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date'); // Sale date or Sale return date
            $table->foreignId('sale_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->foreignId('sale_return_id')->nullable()->constrained('sale_return')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('purchase_price', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->integer('quantity');
            $table->decimal('total', 20, 4);
            $table->decimal('received_amount', 20, 4)->default(0); // Sale invoice payment received from customer
            $table->decimal('paid_amount', 20, 4)->default(0); // Sale return payment paid to customer
            $table->decimal('gross_profit', 20, 4); // Before tax & discount
            $table->decimal('net_profit', 20, 4);   // Actual profit
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_profits');
    }
};
