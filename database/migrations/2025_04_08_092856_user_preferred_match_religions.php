<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserPreferredMatchReligions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_preferred_match_religions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_preferred_match_id')
                ->constrained('user_preferred_matches')
                ->onDelete('cascade');
            $table->string('religion');
            $table->string('denomination')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferred_match_religions');
    }
}
