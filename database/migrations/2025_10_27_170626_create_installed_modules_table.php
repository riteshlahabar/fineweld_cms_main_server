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
        Schema::create('installed_modules', function (Blueprint $table) {
            $table->id();
                $table->string('name');
                $table->string('version');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->text('description')->nullable();

                //$table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installed_modules');
    }
};
