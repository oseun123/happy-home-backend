<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The one doing the favoriting
            $table->foreignId('favorite_user_id')->constrained('users')->onDelete('cascade');
            // The one being favorited
            $table->timestamps();

            $table->unique(['user_id', 'favorite_user_id']); // Prevent duplicates
        });
    }

    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
