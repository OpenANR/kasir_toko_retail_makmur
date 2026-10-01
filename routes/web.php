<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\ClosingCashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

// Halaman Kasir POS (Utama)
Route::get('/', [CashierController::class, 'index'])->name('home');
Route::get('/kasir', [CashierController::class, 'index'])->name('kasir.index');
Route::get('/kasir/cari-produk', [CashierController::class, 'search'])->name('kasir.search');
Route::post('/kasir/transaksi', [CashierController::class, 'store'])->name('kasir.store');

// Parkir / Tahan Transaksi
Route::post('/kasir/tahan', [CashierController::class, 'hold'])->name('kasir.hold');
Route::get('/kasir/transaksi-ditahan', [CashierController::class, 'getHeld'])->name('kasir.held.list');
Route::post('/kasir/transaksi-ditahan/{id}/lanjutkan', [CashierController::class, 'resumeHeld'])->name('kasir.held.resume');
Route::delete('/kasir/transaksi-ditahan/{id}', [CashierController::class, 'destroyHeld'])->name('kasir.held.destroy');

// Cetak Struk
Route::get('/kasir/transaksi/{id}/print', [CashierController::class, 'printReceipt'])->name('kasir.print');
Route::get('/transaksi/{id}/print', [CashierController::class, 'printReceipt']); // Alias dari PDF

// Riwayat Transaksi & Pembatalan
Route::get('/transaksi', [TransactionHistoryController::class, 'index'])->name('transaksi.index');
Route::get('/transaksi/{id}', [TransactionHistoryController::class, 'show'])->name('transaksi.show');
Route::post('/transaksi/{id}/batal', [TransactionHistoryController::class, 'cancel'])->name('transaksi.cancel');

// Manajemen Produk & Stok
Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
Route::post('/produk', [ProductController::class, 'store'])->name('produk.store');
Route::put('/produk/{id}', [ProductController::class, 'update'])->name('produk.update');
Route::delete('/produk/{id}', [ProductController::class, 'destroy'])->name('produk.destroy');

// Rekapitulasi & Tutup Kasir
Route::get('/tutup-kasir', [ClosingCashierController::class, 'index'])->name('kasir.closing');
