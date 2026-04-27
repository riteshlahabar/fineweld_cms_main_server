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
        Schema::create('tally_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->unsignedInteger('port')->nullable();
            $table->unsignedInteger('odbc_port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->boolean('status')->default(1);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tally_integration_settings');
    }
};