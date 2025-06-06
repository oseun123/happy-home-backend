<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightAndLanguageToUserPreferredMatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_preferred_matches', function (Blueprint $table) {
            $table->json('weight_range')->nullable()->after('height_range');
            $table->json('language_spoken')->nullable()->after('weight_range');
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
            $table->dropColumn(['weight_range', 'language_spoken']);
        });
    }
}
