<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\SettingController;

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\MatchmakingController;
use App\Http\Controllers\UserBioDataController;
use App\Http\Controllers\UserContactController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VerifyAddressController;
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
    // delete account
    Route::post('/account/request-deletion', [AccountController::class, 'requestAccountDeletion']);
    Route::post('/account/cancel-deletion', [AccountController::class, 'cancelAccountDeletion']);

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


    // User settings management
    Route::prefix('users/{user}/settings')->group(function () {
        Route::post('/', [UserSettingController::class, 'store']);
        Route::get('/', [UserSettingController::class, 'show']);
        Route::patch('{field}', [UserSettingController::class, 'updateField']);
        Route::post('photos/{field}', [UserSettingController::class, 'updatePhoto']);
    });


    Route::prefix('/users/{user}/dashboard')->group(function () {
        Route::get('/matches', [MatchmakingController::class, 'getUserMatches']);

        Route::post('nugde', [SubscriptionController::class, 'sendNudge']);
        Route::post('/block/{subscribedTo}', [SubscriptionController::class, 'toggleBlock']);
        Route::post('favorite/{targetUserId}', [FavoriteController::class, 'toggleFavorite']);
        Route::get('profile/{targetUserId}', [UserProfileController::class, 'userProfile']);
        Route::get('favorites', [MatchmakingController::class, 'getFavoritesWithMatchScore']);
        Route::get('subscription', [MatchmakingController::class, 'getSubscriptionWithMatchScore']);
        Route::get('intrested', [MatchmakingController::class, 'getInterestedWithMatchScore']);
        Route::get('mutuals', [MatchmakingController::class, 'getMutaulsWithMatchScore']);
    });


    Route::prefix('notifications')->group(function () {

        Route::get('latest', [NotificationController::class, 'latestNotifications']);
        Route::post('mark-as-read/{id}', [NotificationController::class, 'markAsRead']);
        Route::post('clear', [NotificationController::class, 'clearNotifications']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });












    // payment routes
    Route::prefix('paystack')->group(function () {
        // subscription
        Route::post('/{user}/initialize', [SubscriptionController::class, 'initializePayment']);
        Route::post('/verify', [SubscriptionController::class, 'verifyPayment']);

        // address verification
        Route::post('address/{user}/initialize', [VerifyAddressController::class, 'initializePayment']);
        Route::post('address/verify', [VerifyAddressController::class, 'verifyPayment']);
    });
});
