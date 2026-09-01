<?php

namespace App\Http\Controllers;

use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman katalog produk PUBLIK, bisa dilihat siapa saja tanpa login, tempat
 * anggota/non-anggota browse produk sebelum pesan.
 *
 * Controller ini cuma menangani TAMPILAN katalog (Modul 2: Katalog Produk di
 * CLAUDE.md). Tombol "Pesan" di tiap kartu produk (lihat x-catalog.product-card)
 * menyesuaikan siapa yang sedang login: anggota diarahkan ke form pesan anggota,
 * non-anggota ke form pesan non-anggota (sudah FIX 24 Agt 2026), dan tamu yang
 * belum login diarahkan ke pilihan login.
 */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        // Kata kunci pencarian dari kotak cari di halaman katalog. Kalau
        // kosong, scope cari() mengabaikannya (lihat Product::scopeCari).
        $cari = $request->string('cari')->trim()->toString();

        // Cuma produk aktif yang boleh tampil di katalog, dikelompokkan per
        // kategori (mis. "Sembako", "Sayur & Segar") sesuai Modul 2 di CLAUDE.md.
        $productsByCategory = Product::aktif()
            ->cari($cari)
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        // Periode yang sedang dibuka admin, buat ditampilkan di hero banner
        // (kalau tidak ada, banner kasih tau belum ada periode aktif).
        $openPeriod = OrderPeriod::yangSedangDibuka();

        return view('catalog.index', compact('productsByCategory', 'openPeriod', 'cari'));
    }
}
