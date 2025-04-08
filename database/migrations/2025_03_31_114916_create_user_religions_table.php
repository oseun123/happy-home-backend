<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_religions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_bio_data_id')
                ->constrained('user_bio_data') // Ensure it references the correct table name
                ->onDelete('cascade');
            $table->string('religion');
            $table->string('denomination')->nullable();
            $table->timestamps();;
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_religions');
    }
};
