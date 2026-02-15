<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserApiController;

Route::controller(AuthApiController::class)
->prefix('v1')
->as('api.')
->group(function () {
    Route::post('/login', 'login')->name('login');
});
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')
    ->as('api.')
    ->middleware(['auth:sanctum'])
    ->group(function () {
      Route::apiResource('users', UserApiController::class);
      // reset-password
      Route::post('users/reset-password', [UserApiController::class, 'resetPassword'])->name('users.reset-password');
    });