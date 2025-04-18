<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscriber_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscribed_to_id')->constrained('users')->onDelete('cascade');

            $table->unsignedBigInteger('amount_paid')->nullable(); // store in kobo
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->boolean('fully_subscribed')->default(false);
            $table->boolean('is_free_retry')->default(false);
            $table->boolean('free_retry_used')->default(false);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('free_retry_available_at')->nullable();
            $table->boolean('used_free_retry')->default(false);
            $table->boolean('free_retry_granted')->default(false);
            $table->timestamp('reciprocation_deadline')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->json('data')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
