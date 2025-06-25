<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('jwt')->group(function () {
    // User routes
    Route::get('user', [AuthController::class, 'getUser']);
    Route::put('user', [AuthController::class, 'updateUser']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Chat routes
    Route::apiResource('chats', ChatController::class);
    Route::post('chats/{chat}/messages', [ChatController::class, 'sendMessage']);
});
