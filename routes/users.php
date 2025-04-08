<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\UserBioDataController;

use App\Http\Controllers\UserContactController;
use App\Http\Controllers\PersonalProfileController;
use App\Http\Controllers\UserPreferredMatchController;
use App\Http\Controllers\UserHobbiesInterestController;


// users routes

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/settings/{key}', [SettingController::class, 'show']);



    // setup routes
    Route::get('users/hobbies-interests', [UserHobbiesInterestController::class, 'index']);
    Route::get('users/bio-data', [UserBioDataController::class, 'index']);
    Route::get('users/personal-profiles', [PersonalProfileController::class, 'index']);
    Route::get('users/contacts', [UserContactController::class, 'index']);
    Route::prefix('users/{user}')->group(function () {
        // profile
        Route::post('/personal-profile', [PersonalProfileController::class, 'store']);
        Route::get('/personal-profile', [PersonalProfileController::class, 'show']);
        // bio-data
        Route::post('/bio-data', [UserBioDataController::class, 'store']);
        Route::get('/bio-data', [UserBioDataController::class, 'show']);
        Route::put('/bio-data', [UserBioDataController::class, 'update']);
        // contact
        Route::post('/contact', [UserContactController::class, 'store']);
        Route::get('/contact', [UserContactController::class, 'show']);
        Route::put('/contact', [UserContactController::class, 'update']);
        // hobbies & interest
        Route::get('/hobbies-interests', [UserHobbiesInterestController::class, 'show']);   // Get specific user's hobbies & interests
        Route::post('/hobbies-interests', [UserHobbiesInterestController::class, 'store']);  // Store new hobbies & interests
        Route::put('/hobbies-interests', [UserHobbiesInterestController::class, 'update']);
    });


    //  user match  setup
    Route::get('users/preferred-matches', [UserPreferredMatchController::class, 'index']);
    Route::prefix('users/{user}/preferred-matches')->group(function () {
        Route::get('/', [UserPreferredMatchController::class, 'listForUser']);
        Route::post('/', [UserPreferredMatchController::class, 'store']);
        Route::get('/{id}', [UserPreferredMatchController::class, 'show']);
        Route::put('/{id}', [UserPreferredMatchController::class, 'update']);
    });









    // payment routes
    Route::prefix('paystack')->group(function () {

        Route::post('/{user}/initialize', [PaystackController::class, 'initializePayment']);

        Route::post('/verify', [PaystackController::class, 'verifyPayment']);
    });
});
