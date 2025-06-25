<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHobbiesAndInterestToUserPreferredMatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_preferred_matches', function (Blueprint $table) {
            $table->json('hobbies')->nullable()->after('language_spoken');
            $table->json('interest')->nullable()->after('hobbies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_preferred_matches', function (Blueprint $table) {
            $table->dropColumn([
                'hobbies',
                'interest',
            ]);
        });
    }
}
