<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



// super admin routes
Route::prefix('superadmin')->group(function () {

    Route::post('/login', [SuperAdminAuthController::class, 'login']);
    Route::post('/forgot-password', [SuperAdminAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [SuperAdminAuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
    });
});







// users routes

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
