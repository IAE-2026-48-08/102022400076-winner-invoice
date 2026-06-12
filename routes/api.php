<?php

use App\Http\Controllers\WinnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Winner Invoice Service (IAE-T2)
|--------------------------------------------------------------------------
|
| Endpoint publik: GET /api/v1/winners, GET /api/v1/winners/{id}
| Endpoint dilindungi: POST /api/v1/winners
|   - Autentikasi SSO JWT (header Authorization: Bearer <token>)
|   - Autentikasi API Key IAE (header X-IAE-KEY) — jika dikonfigurasi
|
*/

Route::prefix('v1')->group(function () {
    // Endpoint publik
    Route::get('/winners', [WinnerController::class, 'index']);
    Route::get('/winners/{id}', [WinnerController::class, 'show']);

    // Endpoint terlindungi — gunakan sso.jwt (Federated SSO JWT) untuk Tugas 3
    // Ganti 'sso.jwt' dengan 'iae.key' jika menggunakan IAE Key-only auth
    Route::post('/winners', [WinnerController::class, 'store'])->middleware('sso.jwt');
});
