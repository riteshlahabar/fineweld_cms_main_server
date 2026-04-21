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
        Schema::create('partner_party_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->cascadeOnDelete();
            $table->foreignId('party_transaction_id')->nullable()->constrained('party_transactions')->cascadeOnDelete();
            $table->string('unique_code');
            $table->foreignId('payment_type_id')->nullable()->constrained('payment_types');
            $table->decimal('amount', 20, 4);
            $table->foreignId('partner_id')->constrained('partners');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_party_transactions');
    }
};
