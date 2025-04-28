<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressVerificationPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_address_verification_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->integer('amount');
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('longitude');
            $table->string('latitude');
            $table->boolean('verified_address')->default(0);
            $table->json('paystack_data')->nullable();
            $table->json('dojah_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_address_verification_payments');
    }
}
