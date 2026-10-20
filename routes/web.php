<?php

use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\PublicInvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman publik pesanan, dibuka dari QR di invoice. Dibatasi agar token tidak bisa ditebak.
Route::get('/p/{token}', [PublicInvoiceController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('invoice.public');

// Tombol "Bayar dengan QRIS". Dibatasi karena menghubungi Midtrans.
Route::post('/p/{token}/pay', [PublicInvoiceController::class, 'pay'])
    ->middleware('throttle:5,1')
    ->name('invoice.public.pay');

// Invoice cetak untuk admin. Pemeriksaan admin ada di controller.
Route::get('/cetak/invoice/{order}', [InvoicePrintController::class, 'show'])
    ->whereNumber('order')
    ->name('invoice.print');