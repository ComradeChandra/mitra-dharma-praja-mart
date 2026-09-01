<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Autentikasi Admin
|--------------------------------------------------------------------------
| Hanya berisi login & logout. TIDAK ada registrasi mandiri (admin dibuat
| lewat seeder database, lihat database/seeders/AdminUserSeeder.php), dan
| TIDAK ada reset password / verifikasi email (project belum setup
| pengiriman email sungguhan). Sesuai rencana Tahap 1 di CLAUDE.md.
*/

// Middleware 'guest': hanya bisa diakses kalau BELUM login. Kalau sudah
// login lalu buka /login lagi, otomatis dialihkan (guard di middleware).
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Middleware 'auth': hanya bisa diakses kalau sudah login.
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
