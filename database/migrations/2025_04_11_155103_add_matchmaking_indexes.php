<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMatchmakingIndexes extends Migration
{
    public function up()
    {
        //     // Users table indexes - only basic indexes since most fields are in related tables
        //     Schema::table('users', function (Blueprint $table) {
        //         $table->index('id'); // Primary key is already indexed by default
        //     });



        //     // Contacts table indexes - this is where nationality and state actually exist
        //     Schema::table('user_contacts', function (Blueprint $table) {
        //         $table->index('user_id');
        //         $table->index('nationality');
        //         $table->index('state');
        //         $table->index(['nationality', 'state']); // Composite index
        //         $table->index(['nationality', 'state', 'user_id']); // Additional composite index
        //     });

        //     // User bio data table indexes
        //     Schema::table('user_bio_data', function (Blueprint $table) {
        //         $table->index('user_id');
        //         $table->index('marital_status');
        //         $table->index('ethnicity');
        //         $table->index('genotype');
        //         $table->index(['marital_status', 'ethnicity', 'genotype']);
        //     });

        //     // Personal profiles table indexes
        //     Schema::table('personal_profiles', function (Blueprint $table) {
        //         $table->index('user_id');
        //         $table->index('gender');
        //         $table->index('date_of_birth');
        //         $table->index(['gender', 'date_of_birth']);
        //     });
        // }
    }
}
