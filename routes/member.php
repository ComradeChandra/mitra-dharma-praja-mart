<?php

use App\Http\Controllers\Member\AuthenticatedSessionController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\OrderController;
use App\Http\Controllers\Member\ProductRequestController;
use App\Http\Controllers\Member\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Anggota (guard 'member')
|--------------------------------------------------------------------------
| Login anggota terpisah dari login admin — anggota TIDAK bikin akun sendiri,
| password dibuatkan admin lewat form kelola anggota (lihat CLAUDE.md,
| "Perubahan Requirement"). Semua route di sini pakai prefix '/anggota' +
| nama route 'member.*'.
*/
Route::prefix('anggota')->name('member.')->group(function () {
    // Middleware 'guest:member': cuma bisa diakses kalau BELUM login sebagai anggota
    Route::middleware('guest:member')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    // Middleware 'auth:member': cuma bisa diakses kalau sudah login sebagai anggota
    Route::middleware('auth:member')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('beranda', [DashboardController::class, 'index'])->name('dashboard');

        // Permintaan produk baru (Modul 8), sisi anggota. Cuma index/create/store
        // karena anggota tidak perlu edit/hapus permintaan yang sudah dikirim.
        Route::get('permintaan-produk', [ProductRequestController::class, 'index'])->name('product-requests.index');
        Route::get('permintaan-produk/baru', [ProductRequestController::class, 'create'])->name('product-requests.create');
        Route::post('permintaan-produk', [ProductRequestController::class, 'store'])->name('product-requests.store');

        // Pemesanan (Modul 3), sisi anggota. "pesan" = form isi jumlah per
        // produk & kirim; "pesanan-saya" = riwayat pesanan yang pernah dikirim.
        Route::get('pesan', [OrderController::class, 'create'])->name('orders.create');
        Route::post('pesan', [OrderController::class, 'store'])->name('orders.store');
        Route::get('pesanan-saya', [OrderController::class, 'index'])->name('orders.index');
        Route::get('pesanan-saya/{order}', [OrderController::class, 'show'])->name('orders.show');
        // Struk resmi yang siap dicetak atau disimpan jadi PDF lewat browser
        Route::get('pesanan-saya/{order}/struk', [OrderController::class, 'struk'])->name('orders.struk');

        // Profil anggota, ubah foto, nomor WhatsApp, alamat, & password
        // sendiri. Nama & kode anggota tidak bisa diubah di sini (tetap
        // dikelola pengurus, lihat Member\ProfileController).
        Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    });
});
