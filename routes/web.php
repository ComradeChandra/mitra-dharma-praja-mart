<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OpdDepartmentController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPeriodController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductRequestController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\LoginPortalController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman katalog produk PUBLIK, bisa dibuka siapa saja tanpa login.
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');

// Halaman "Masuk" terpadu, satu pintu buat anggota, non-anggota, & pengurus,
// dipilih lewat dropdown peran. Cuma menampilkan formulirnya; proses login
// tetap ditangani controller masing-masing (lihat LoginPortalController).
Route::get('/masuk', LoginPortalController::class)->name('masuk');

// Halaman depan ('/'):
// - Sudah login (admin)  -> langsung ke dashboard admin
// - Belum login          -> ke halaman katalog publik (bukan dipaksa login lagi,
//   karena sekarang sudah ada halaman publik yang layak jadi "beranda")
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('catalog.index');
});

/*
|--------------------------------------------------------------------------
| Route Admin
|--------------------------------------------------------------------------
| Semua route di sini wajib login (middleware 'auth') dan diberi prefix
| '/admin' + nama route 'admin.*' supaya jelas terpisah dari nanti route
| anggota/non-anggota (yang tanpa password). Halaman ini akan bertambah
| terus di Tahap 2 (Modul Admin): produk, anggota, OPD, periode, dst.
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route::resource otomatis bikin 7 route standar (index, create, store, edit,
    // update, destroy, show) sekaligus dari 1 baris. 'show' di-except karena OPD
    // tidak butuh halaman detail terpisah (cukup index + edit).
    Route::resource('opd-departments', OpdDepartmentController::class)->except(['show']);
    Route::resource('members', MemberController::class)->except(['show']);
    Route::resource('order-periods', OrderPeriodController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);

    // Rekap belanja grosir per periode (Modul 6), bukan bagian resource CRUD
    // di atas (makanya 'show' di-except), route detail sendiri karena isinya
    // rekap, bukan form edit.
    Route::get('order-periods/{orderPeriod}/rekap', [OrderPeriodController::class, 'rekap'])->name('order-periods.rekap');

    // Pemesanan (Modul 3, 5 & 7), admin lihat semua pesanan masuk, mengunci
    // harga produk fluktuatif ("verifikasi"), lalu kirim invoice WhatsApp.
    // Bukan CRUD biasa (tidak ada create/edit/delete, pesanan hanya dibuat
    // anggota, bukan admin).
    Route::get('pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('pesanan/{order}/struk', [OrderController::class, 'struk'])->name('orders.struk');
    Route::patch('pesanan/{order}/verifikasi', [OrderController::class, 'verify'])->name('orders.verify');
    Route::patch('pesanan/{order}/tandai-terkirim', [OrderController::class, 'markInvoiced'])->name('orders.mark-invoiced');

    // Peninjauan permintaan produk (Modul 8), admin cuma approve/reject,
    // bukan CRUD penuh (tidak ada create/edit/delete dari sisi admin).
    Route::get('product-requests', [ProductRequestController::class, 'index'])->name('product-requests.index');
    Route::patch('product-requests/{productRequest}/approve', [ProductRequestController::class, 'approve'])->name('product-requests.approve');
    Route::patch('product-requests/{productRequest}/reject', [ProductRequestController::class, 'reject'])->name('product-requests.reject');

    // Profil admin sendiri, ganti nama/email & ganti password. Sebelumnya
    // cuma bisa lewat tinker, sekarang ada halamannya.
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Route login/logout admin (lihat routes/auth.php)
require __DIR__.'/auth.php';

// Route login/logout & beranda anggota (lihat routes/member.php)
require __DIR__.'/member.php';

// Route "login"/pesanan non-anggota (lihat routes/non-member.php)
require __DIR__.'/non-member.php';
