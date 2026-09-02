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

    /**
     * Halaman detail satu produk.
     *
     * Produk nonaktif ditolak dengan 404, bukan sekadar disembunyikan dari
     * daftar. Kalau cuma disembunyikan, halamannya masih bisa dibuka dengan
     * menebak-nebak angka di URL.
     *
     * Angka stok TIDAK pernah dikirim ke halaman ini. Yang ditampilkan cuma
     * status tersedia atau tidak, lewat Product::isAvailable() (lihat aturan
     * soal stok di CLAUDE.md).
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        // Beberapa produk lain di kategori yang sama, biar dari halaman ini
        // masih bisa melihat-lihat tanpa balik dulu ke katalog.
        $serupa = Product::aktif()
            ->where('category', $product->category)
            ->whereKeyNot($product->id)
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('catalog.show', compact('product', 'serupa'));
    }
}
