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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('irn', 120)->nullable()->after('exchange_rate');
            $table->string('irn_ack_no', 100)->nullable()->after('irn');
            $table->string('irn_ack_date', 100)->nullable()->after('irn_ack_no');
            $table->longText('irn_signed_qr_code')->nullable()->after('irn_ack_date');
            $table->string('ewb_no', 100)->nullable()->after('irn_signed_qr_code');
            $table->string('ewb_date', 100)->nullable()->after('ewb_no');
            $table->string('ewb_valid_till', 100)->nullable()->after('ewb_date');
            $table->string('einvoice_status', 50)->nullable()->after('ewb_valid_till');
            $table->text('einvoice_error')->nullable()->after('einvoice_status');
            $table->timestamp('einvoice_synced_at')->nullable()->after('einvoice_error');

            $table->index('irn');
            $table->index('ewb_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['irn']);
            $table->dropIndex(['ewb_no']);
            $table->dropColumn([
                'irn',
                'irn_ack_no',
                'irn_ack_date',
                'irn_signed_qr_code',
                'ewb_no',
                'ewb_date',
                'ewb_valid_till',
                'einvoice_status',
                'einvoice_error',
                'einvoice_synced_at',
            ]);
        });
    }
};

