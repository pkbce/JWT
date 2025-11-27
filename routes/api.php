<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ConsumptionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);


    // Social Authentication Routes
    Route::get('login/{provider}', [SocialAuthController::class, 'redirectToProvider']);
    Route::get('login/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);



    Route::middleware('auth:api')->group(function () {
        Route::post('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('reset-password', [ResetPasswordController::class, 'reset']);

Route::get('ll_db_route', [AuthController::class, 'll_db_route']);
Route::get('ml_db_route', [AuthController::class, 'ml_db_route']);
Route::get('hl_db_route', [AuthController::class, 'hl_db_route']);
Route::get('ul_db_route', [AuthController::class, 'ul_db_route']);

Route::post('ll_change_power_status', [AuthController::class, 'll_change_power_status']);
Route::post('ml_change_power_status', [AuthController::class, 'ml_change_power_status']);
Route::post('hl_change_power_status', [AuthController::class, 'hl_change_power_status']);
Route::post('ul_change_power_status', [AuthController::class, 'ul_change_power_status']);

Route::delete('ll_delete_row', [AuthController::class, 'll_delete_row']);
Route::delete('ml_delete_row', [AuthController::class, 'ml_delete_row']);
Route::delete('hl_delete_row', [AuthController::class, 'hl_delete_row']);
Route::delete('ul_delete_row', [AuthController::class, 'ul_delete_row']);

Route::post('ll_change_socket_name', [AuthController::class, 'll_change_socket_name']);
Route::post('ml_change_socket_name', [AuthController::class, 'ml_change_socket_name']);
Route::post('hl_change_socket_name', [AuthController::class, 'hl_change_socket_name']);
Route::post('ul_change_socket_name', [AuthController::class, 'ul_change_socket_name']);

Route::post('ll_add_socket', [AuthController::class, 'll_add_socket']);
Route::post('ml_add_socket', [AuthController::class, 'ml_add_socket']);
Route::post('hl_add_socket', [AuthController::class, 'hl_add_socket']);
Route::post('ul_add_socket', [AuthController::class, 'ul_add_socket']);

Route::post('create_db', [AuthController::class, 'create_db']);
Route::post('change_username', [AuthController::class, 'change_username']);

Route::post('ll_update_consumption', [AuthController::class, 'll_update_consumption']);
Route::post('ml_update_consumption', [AuthController::class, 'ml_update_consumption']);
Route::post('hl_update_consumption', [AuthController::class, 'hl_update_consumption']);
Route::post('ul_update_consumption', [AuthController::class, 'ul_update_consumption']);

Route::post('ll_reset_consumption', [AuthController::class, 'hl_reset_consumption']);
Route::post('ml_reset_consumption', [AuthController::class, 'ml_reset_consumption']);
Route::post('hl_reset_consumption', [AuthController::class, 'hl_reset_consumption']);
Route::post('ul_reset_consumption', [AuthController::class, 'ul_reset_consumption']);


// Consumption data endpoints
Route::get('consumption/data', [ConsumptionController::class, 'getConsumptionData']);
Route::get('consumption/history', [ConsumptionController::class, 'getConsumptionHistory']);
Route::post('consumption/sync-firebase', [ConsumptionController::class, 'syncFirebaseData']);
Route::post('consumption/check-reset', [ConsumptionController::class, 'checkReset']);
