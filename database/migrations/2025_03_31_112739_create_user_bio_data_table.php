<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('user_bio_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique();
            $table->string('height_range');
            $table->string('weight_range');
            $table->string('ethnicity');
            $table->string('genotype');
            $table->string('marital_status');
            $table->string('occupation');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_bio_data');
    }
};
