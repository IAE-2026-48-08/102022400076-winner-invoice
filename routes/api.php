<?php

use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public routes (for viewing winners and invoices)
    Route::get('/winners', [WinnerController::class, 'index']);
    Route::get('/winners/{id}', [WinnerController::class, 'show']);

    // Protected route (requires Federated SSO JWT)
    Route::post('/winners', [WinnerController::class, 'store'])->middleware('sso.jwt');
});
