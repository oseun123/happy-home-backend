<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetryFieldsToUserAddressVerificationPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_address_verification_payments', function (Blueprint $table) {
            $table->integer('retry_count')->default(0);
            $table->integer('retry_limit')->default(3);
            $table->string('dojah_reference_id')->nullable();
            $table->string('dojah_verification_status')->nullable();
            $table->text('verification_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_address_verification_payments', function (Blueprint $table) {
            $table->dropColumn([
                'retry_count',
                'retry_limit',
                'dojah_reference_id',
                'dojah_verification_status',
                'verification_message'
            ]);
        });
    }
}
