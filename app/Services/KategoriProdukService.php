<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Kategori produk: daftar kategori yang sudah dipakai, dan perapian isian
 * kategori sebelum produk disimpan.
 *
 * KENAPA ADA: kategori diketik bebas, sedangkan katalog mengelompokkan produk
 * berdasarkan tulisan kategori yang PERSIS sama. "Sembako", "sembako", dan
 * "Sembako " (spasi di akhir) akan muncul sebagai tiga kelompok terpisah.
 * Kategori sengaja tidak dibuat tabel sendiri: untuk ±10 produk, saran dari
 * kategori yang sudah ada + perapian ini sudah cukup.
 */
class KategoriProdukService
{
    /**
     * Semua kategori yang sedang dipakai produk (termasuk produk nonaktif),
     * urut abjad. Dipakai sebagai daftar saran (datalist) di form produk.
     *
     * @return Collection<int, string>
     */
    public function daftar(): Collection
    {
        return Product::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Rapikan isian kategori:
     * - spasi di awal/akhir dibuang, spasi berderet dijadikan satu;
     * - kalau isian sama dengan kategori yang sudah ada (tanpa membedakan
     *   huruf besar-kecil), tulisan yang sudah ada yang dipakai, supaya
     *   "sembako" ikut masuk kelompok "Sembako".
     *
     * $kecualiProdukId: produk yang sedang diubah TIDAK ikut dibandingkan.
     * Tanpa ini, satu-satunya produk berkategori "sembako" tidak akan pernah
     * bisa dibetulkan jadi "Sembako", karena isiannya selalu dikembalikan ke
     * tulisan lamanya sendiri.
     */
    public function rapikan(string $masukan, ?int $kecualiProdukId = null): string
    {
        $rapi = trim(preg_replace('/\s+/u', ' ', $masukan));

        $sudahAda = Product::query()
            ->when($kecualiProdukId, fn ($query) => $query->whereKeyNot($kecualiProdukId))
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->first(fn (string $kategori) => mb_strtolower($kategori) === mb_strtolower($rapi));

        return $sudahAda ?? $rapi;
    }
}
