<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('admin_devices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('admin_id');
        $table->text('fcm_token');
        $table->timestamps();

        $table->index('admin_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_devices');
    }
};
