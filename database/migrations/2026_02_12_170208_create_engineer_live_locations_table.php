<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engineer_live_locations', function (Blueprint $table) {
            $table->id();

            // HRMS employee id
            $table->unsignedBigInteger('employee_id')->unique();

            // Basic Info
            $table->string('employee_name');

            // GPS Coordinates
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('address')->nullable();

            // Optional: current status (active/moving/available)
            $table->string('status')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engineer_live_locations');
    }
};
