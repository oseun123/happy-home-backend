<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserPreferredMatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('user_preferred_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('relationship_type');
            $table->json('gender')->nullable();
            $table->json('age_range')->nullable();
            $table->json('marital_status')->nullable();
            $table->json('ethnicity')->nullable();
            $table->json('genotype')->nullable();
            $table->json('nationality')->nullable();
            $table->json('state')->nullable();
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
        Schema::dropIfExists('user_preferred_matches');
    }
}
