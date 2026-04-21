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
    Schema::create('parties', function (Blueprint $table) {

        $table->id();

        // Basic Info
        $table->string('prefix_code')->nullable();
        $table->unsignedBigInteger('count_id')->nullable();
        $table->string('party_code')->nullable();
        $table->string('party_type')->nullable();

        // Company Info
        $table->string('company_name')->nullable();
        $table->enum('company_type', [
            'proprietor',
            'partnership',
            'private_limited',
            'public_limited',
            'one_person_company',
            'limited_liability_partnership'
        ])->nullable();

        $table->enum('vendor_type', ['customer', 'supplier', 'both'])->nullable();
        $table->string('company_pan')->nullable();
        $table->string('company_gst')->nullable();
        $table->string('company_tan')->nullable();
        $table->string('company_msme')->nullable();
        $table->date('date_of_incorporation')->nullable();
        $table->string('contact_person')->nullable();

        // Primary Contact
        $table->string('primary_name')->nullable();
        $table->string('primary_email')->nullable();
        $table->string('primary_mobile')->nullable();
        $table->string('primary_whatsapp')->nullable();
        $table->date('primary_dob')->nullable();

        // Secondary Contact
        $table->string('secondary_name')->nullable();
        $table->string('secondary_email')->nullable();
        $table->string('secondary_mobile')->nullable();
        $table->string('secondary_whatsapp')->nullable();
        $table->date('secondary_dob')->nullable();

        // Bank Details
        $table->string('bank_name')->nullable();
        $table->string('bank_branch')->nullable();
        $table->string('bank_account_no')->nullable();
        $table->string('ifsc_code')->nullable();
        $table->string('micr_code')->nullable();

        // Documents
        $table->string('pan_document')->nullable();
        $table->string('tan_document')->nullable();
        $table->string('gst_document')->nullable();
        $table->string('msme_document')->nullable();
        $table->string('cancelled_cheque')->nullable();

        // Business Flags
        $table->boolean('is_wholesale_customer')->default(0);
        $table->boolean('default_party')->default(0);

        // Addresses
        $table->text('billing_address')->nullable();
        $table->text('shipping_address')->nullable();

        // Financial
        $table->unsignedBigInteger('currency_id')->nullable();
        $table->decimal('exchange_rate', 15, 4)->default(0);

        // Location
        $table->unsignedBigInteger('state_id')->nullable();

        // Credit
        $table->boolean('is_set_credit_limit')->default(0);

        // Audit
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();

        $table->boolean('status')->default(1);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'company_address', 'company_type', 'vendor_type',
                'company_pan', 'company_gst', 'company_tan', 'company_msme', 
                'date_of_incorporation', 'contact_person',
                'primary_name', 'primary_email', 'primary_mobile', 'primary_whatsapp', 'primary_dob',
                'secondary_name', 'secondary_email', 'secondary_mobile', 'secondary_whatsapp', 'secondary_dob',
                'bank_name', 'bank_branch', 'bank_account_no', 'ifsc_code', 'micr_code',
                'pan_document', 'tan_document', 'gst_document', 'msme_document', 'cancelled_cheque'
            ]);
        });
    }
};
