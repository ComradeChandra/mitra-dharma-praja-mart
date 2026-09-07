<?php

use App\Http\Controllers\NonMember\AuthenticatedSessionController;
use App\Http\Controllers\NonMember\OrderController;
use App\Http\Controllers\NonMember\ProductRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Non-Anggota (sesi 'non_member_opd_id', BUKAN guard Laravel)
|--------------------------------------------------------------------------
| Alurnya sudah final, lihat
| CLAUDE.md Lampiran B). Non-anggota "login" pakai kode akses OPD BERSAMA
| (bukan akun personal), makanya route di sini pakai middleware
| 'non-member.session' (custom, lihat EnsureNonMemberSession) — bukan
| 'auth:member' seperti anggota. Semua route pakai prefix '/non-anggota' +
| nama route 'non-member.*', terpisah total dari anggota (isolasi sesuai
| arahan CLAUDE.md).
*/
Route::prefix('non-anggota')->name('non-member.')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::middleware('non-member.session')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('pesan', [OrderController::class, 'create'])->name('orders.create');
        Route::post('pesan', [OrderController::class, 'store'])->name('orders.store');
        Route::get('pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('pesanan/{order}/struk', [OrderController::class, 'struk'])->name('orders.struk');
        Route::post('pesanan/{order}/bayar', [OrderController::class, 'declarePaid'])->name('orders.declare-paid');
        Route::get('pesanan/{order}/bukti-bayar', [OrderController::class, 'paymentProof'])->name('orders.payment-proof');

        // Usulan produk baru (Modul 8), sisi non-anggota. Cuma create/store:
        // tidak ada halaman riwayat karena tidak ada identitas personal yang
        // stabil buat difilter (lihat NonMember\ProductRequestController).
        Route::get('usul-produk', [ProductRequestController::class, 'create'])->name('product-requests.create');
        Route::post('usul-produk', [ProductRequestController::class, 'store'])->name('product-requests.store');
    });
});
