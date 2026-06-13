<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest.sso')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth.sso')->group(function () {
    Route::get('/', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/winners', function () {
        return view('winners.index');
    });

    Route::get('/winners/{id}', function ($id) {
        return view('winners.show', ['id' => $id]);
    });

    Route::get('/checkout', function () {
        return view('winners.create');
    });
    Route::post('/checkout', [\App\Http\Controllers\WinnerController::class, 'store'])
        ->name('checkout.store');

    Route::get('/token', function () {
        return view('token');
    });
});
