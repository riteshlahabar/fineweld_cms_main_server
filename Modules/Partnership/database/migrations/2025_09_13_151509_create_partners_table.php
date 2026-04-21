<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seeder = new \Database\Seeders\AdminSeeder();
        $seeder->run();

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('prefix_code')->nullable();
            $table->string('count_id')->nullable();
            $table->string('partner_code')->nullable();
            $table->string('partner_type')->nullable(); // business, individual, organization
            $table->boolean('default_partner')->default(0);

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();

            $table->string('tax_number')->nullable();
            $table->string('tax_type')->nullable(); // Only if GST enabled, unregistered, registered

            $table->string('company_name')->nullable();
            $table->string('website')->nullable();

            $table->decimal('to_pay', 20, 4)->default(0);
            $table->decimal('to_receive', 20, 4)->default(0);

            $table->unsignedBigInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies');

            // Only if GST enabled
            $table->unsignedBigInteger('state_id')->nullable();
            $table->foreign('state_id')->references('id')->on('states');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users');

            $table->boolean('status')->default(1);

            $table->timestamps();
        });

        DB::table('partners')->insert([
            'prefix_code' => 'PTNR',
            'count_id' => '1',
            'partner_code' => 'PTNR1',
            'partner_type' => 'business',
            'default_partner' => 1,
            'first_name' => 'Default',
            'last_name' => 'Partner',
            'email' => 'default@partner.com',
            'mobile' => null,
            'phone' => null,
            'whatsapp' => null,
            'address' => null,
            'tax_number' => null,
            'tax_type' => null,
            'company_name' => 'Default Company',
            'website' => null,
            'to_pay' => 0,
            'to_receive' => 0,
            'currency_id' => null,
            'state_id' => null,
            'created_by' => 1,
            'updated_by' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
