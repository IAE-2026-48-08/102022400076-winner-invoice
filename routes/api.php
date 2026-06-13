<?php

use App\Http\Controllers\WinnerController;
use App\Models\AuctionItem;
use App\Models\User;
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

    // Endpoint untuk UI frontend
    Route::get('/users', function () {
        $users = User::select('id', 'name', 'email')->get();
        return response()->json([
            'status' => 'success',
            'data' => $users,
            'meta' => [
                'service_name' => 'Winner-Service',
                'api_version' => 'v1',
            ],
        ]);
    });

    Route::get('/auction-items', function () {
        $items = AuctionItem::whereDoesntHave('winner')
            ->select('id', 'name', 'description', 'final_price', 'status')
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $items,
            'meta' => [
                'service_name' => 'Winner-Service',
                'api_version' => 'v1',
            ],
        ]);
    });

    Route::get('/stats', function () {
        $totalWinners = \App\Models\Winner::count();
        $totalInvoices = \App\Models\Invoice::count();
        $totalRevenue = \App\Models\Invoice::sum('amount');
        $pendingInvoices = \App\Models\Invoice::where('status', 'pending')->count();
        $paidInvoices = \App\Models\Invoice::where('status', 'paid')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_winners' => $totalWinners,
                'total_invoices' => $totalInvoices,
                'total_revenue' => (float) $totalRevenue,
                'pending_invoices' => $pendingInvoices,
                'paid_invoices' => $paidInvoices,
            ],
            'meta' => [
                'service_name' => 'Winner-Service',
                'api_version' => 'v1',
            ],
        ]);
    });

    // Endpoint terlindungi — gunakan sso.jwt (Federated SSO JWT) untuk Tugas 3
    // Ganti 'sso.jwt' dengan 'iae.key' jika menggunakan IAE Key-only auth
    Route::post('/winners', [WinnerController::class, 'store'])->middleware('sso.jwt');
});
