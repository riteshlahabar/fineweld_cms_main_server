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
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->date('contract_date'); // start date
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_id')->constrained();

            $table->foreignId('item_id')->constrained()->index('item_id');
            $table->text('description')->nullable();

            // $table->date('effective_from')->nullable(); // start date
            // $table->date('effective_to')->nullable(); // end date

            $table->enum('share_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('share_value', 8, 2); // e.g. 20, 30

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
