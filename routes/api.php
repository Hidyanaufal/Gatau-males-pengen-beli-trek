<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MidtransWebhookController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Middleware\EnsureCustomerIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Pendaftaran dan login dibatasi: 10 permintaan per menit per IP.
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Katalog layanan (hanya baca, layanan aktif saja).
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'show'])->whereNumber('id');

    // Webhook dari Midtrans. Tanpa token Sanctum; keamanan lewat validasi signature.
    Route::post('payments/midtrans/notification', [MidtransWebhookController::class, 'handle'])
        ->middleware('throttle:120,1');

    // Endpoint privat: wajib token Sanctum dan akun pelanggan aktif.
    Route::middleware(['auth:sanctum', EnsureCustomerIsActive::class, 'throttle:60,1'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');
        Route::get('orders/{id}/payment', [OrderController::class, 'payment'])->whereNumber('id');

        // Minta QR pembayaran (QRIS). Dibatasi 10 permintaan per menit.
        Route::post('orders/{id}/pay', [PaymentController::class, 'pay'])
            ->whereNumber('id')
            ->middleware('throttle:10,1');
    });
});