<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuperAdminAuthController;




// super admin routes
Route::prefix('superadmin')->group(function () {

    Route::post('/login', [SuperAdminAuthController::class, 'login']);
    Route::post('/forgot-password', [SuperAdminAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [SuperAdminAuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);


        // settings routes
        Route::get('/settings', [SettingController::class, 'index']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::put('/settings/{key}', [SettingController::class, 'update']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);


        //admin dashboad

        Route::get('dashboard-stats', [StatsController::class, 'getDashboardStats']);
        Route::get('dashboard-stats-yearly', [StatsController::class, 'getMonthlySales']);
        Route::get('dashboard-stats-monthly', [StatsController::class, 'getDailySalesForMonth']);
        Route::get('dashboard-user-list', [StatsController::class, 'getUserList']);
        Route::get('dashboard-user-list-deleted', [StatsController::class, 'getDeletedUserList']);
    });
});
