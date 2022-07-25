<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware("throttle:3,1");
Route::post('transfer-charges', [\App\Http\Controllers\AuthController::class, 'transfer_charges']);


Route::middleware('auth:sanctum')->group(function () {
	Route::get('user', [\App\Http\Controllers\AuthController::class, 'user']);
	Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout']);
	Route::post('change-password', [\App\Http\Controllers\AuthController::class, 'change_password']);
	Route::post('update-address', [\App\Http\Controllers\AuthController::class, 'update_address']);
	Route::post('local-transfer', [\App\Http\Controllers\AuthController::class, 'local_transfer']);
	Route::post('international-transfer', [\App\Http\Controllers\AuthController::class, 'international_transfer']);
	Route::post('search-user', [\App\Http\Controllers\AuthController::class, 'search_user']);
	Route::post('exchange-rate', [\App\Http\Controllers\AuthController::class, 'exchange_rates']);
});