<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
       Schema::create('tickets', function (Blueprint $table) {
    $table->id();

    $table->string('ticket_no')->unique();

    $table->foreignId('party_id')
        ->constrained('parties')
        ->onDelete('cascade');

    $table->foreignId('product_id')
        ->constrained('products')
        ->onDelete('cascade');

    $table->text('problem');
    
    $table->text('problem_description')->nullable();

    $table->foreignId('priority_id')
        ->constrained('masters_ticket_status');

    $table->foreignId('status_id')
        ->constrained('masters_ticket_status');

    $table->foreignId('service_type_id')
        ->constrained('masters_ticket_status');
    
    $table->foreignId('visit_type_id')
        ->constrained('masters_ticket_status');    
        
    $table->timestamp('closed_at')->nullable();    

    $table->string('technician_id')->nullable();
    
    $table->timestamp('scheduled_at')->nullable();
    
    $table->boolean('is_active')->default(0);

    $table->timestamps();
});

    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
};
