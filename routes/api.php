<?php
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\UserApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {
        return response()->json([
            'message' => 'Welcome to the User Management API',
        ]);
    });

    Route::post('/login', [AuthApiController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/users/reset-password', [UserApiController::class, 'resetPassword'])
        ->middleware('throttle:3,1');

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthApiController::class, 'logout']);

        Route::apiResource('users', UserApiController::class)
            ->middleware('permission:manage_users');

        Route::get('audit-logs', [UserApiController::class, 'auditLogs'])
            ->middleware('permission:view_audit_logs');
    });
});
