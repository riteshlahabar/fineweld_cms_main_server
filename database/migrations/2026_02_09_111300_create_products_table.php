<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('party_id');

            $table->string('purchase_order_no')->nullable();
            $table->date('purchase_order_date')->nullable();
            $table->string('tax_invoice_no')->nullable();
            $table->date('tax_invoice_date')->nullable();

            $table->string('product_name');
            $table->string('model_number')->nullable();
            $table->string('serial_number')->unique();
            $table->string('product_image')->nullable();

            $table->date('installation_date')->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();

            $table->string('installed_by')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->foreign('party_id')
                ->references('id')
                ->on('parties')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
