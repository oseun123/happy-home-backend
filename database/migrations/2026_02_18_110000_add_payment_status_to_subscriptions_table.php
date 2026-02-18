<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentStatusToSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     * Adds a nullable 'payment_status' column to track Paystack's reported
     * transaction status (e.g. null = unknown, 'success', 'abandoned', 'failed').
     * Null means we haven't checked yet — existing records are unaffected.
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('verified')
                ->comment('Paystack transaction status: null=unchecked, success, abandoned, failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
}
