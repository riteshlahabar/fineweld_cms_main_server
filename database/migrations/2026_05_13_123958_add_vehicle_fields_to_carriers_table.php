<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('whatsapp');
            $table->string('vehicle_no')->nullable()->after('vehicle_type');
            $table->string('transporter_id')->nullable()->after('vehicle_no');
        });
    }

    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'vehicle_no', 'transporter_id']);
        });
    }
};