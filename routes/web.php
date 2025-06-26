<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard/{chat?}', function ($chat = null) {
    return Inertia::render('Dashboard', [
        'chatId' => $chat
    ]);
})->where('chat', '[0-9]+')->middleware(['auth', 'verified'])->name('dashboard');

// Keep the original dashboard route for backward compatibility
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard.index');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
