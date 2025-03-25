<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperadminAuthController;

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

    Route::post('/login', [SuperadminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
        Route::post('/logout', [SuperadminAuthController::class, 'logout']);
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Welcome, Superadmin']);
        });
    });
});







// users routes

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
