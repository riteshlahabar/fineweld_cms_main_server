<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineer_devices', function (Blueprint $table) {
            $table->id();

            // This matches tickets.technician_id (HRMS engineer ID)
            $table->unsignedBigInteger('engineer_id');

            // Firebase token
            $table->text('fcm_token');

            $table->string('device_type')->nullable(); // android / ios
            $table->timestamps();

            // Optional index for faster lookup
            $table->index('engineer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineer_devices');
    }
};
