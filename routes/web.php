<?php

use App\Http\Controllers\PublicInvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman publik pesanan, dibuka dari QR di invoice. Dibatasi agar token tidak bisa ditebak.
Route::get('/p/{token}', [PublicInvoiceController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('invoice.public');