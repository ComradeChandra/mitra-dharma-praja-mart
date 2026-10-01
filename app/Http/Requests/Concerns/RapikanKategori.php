<?php

namespace App\Http\Requests\Concerns;

use App\Models\Product;
use App\Services\KategoriProdukService;

/**
 * Menentukan dan merapikan kategori SEBELUM divalidasi dan disimpan. Dipakai
 * bareng oleh StoreProductRequest dan UpdateProductRequest supaya aturannya
 * cuma ada di satu tempat (KategoriProdukService::rapikan).
 *
 * Form mengirim dua kolom (komponen x-admin.pilih-kategori):
 * - category      : kategori yang dipilih dari dropdown, ATAU penanda
 *                   "+ Buat kategori baru" (KategoriProdukService::PILIHAN_BARU)
 * - category_baru : nama kategori baru yang diketik
 * Setelah lewat sini, yang divalidasi & disimpan cukup satu: category.
 */
trait RapikanKategori
{
    protected function prepareForValidation(): void
    {
        $masukan = $this->input('category');

        // "+ Buat kategori baru" dipilih: nama kategorinya ada di category_baru.
        // Kalau kolom itu kosong/aneh, jadikan kosong supaya ditolak aturan
        // 'required' dengan pesan "Kategori produk wajib diisi.", bukan malah
        // tersimpan sebagai kategori bernama "__baru__".
        if ($masukan === KategoriProdukService::PILIHAN_BARU) {
            $masukan = is_string($this->input('category_baru')) ? $this->input('category_baru') : '';
        }

        // Isian aneh lain (array, dsb) dibiarkan apa adanya: aturan 'string'
        // di rules() yang akan menolaknya dengan pesan yang jelas.
        if (! is_string($masukan)) {
            return;
        }

        // Saat mengubah produk, rute membawa produknya ({product}); produk itu
        // tidak ikut dibandingkan. Saat menambah produk, tidak ada.
        $produk = $this->route('product');

        $this->merge([
            'category' => app(KategoriProdukService::class)->rapikan(
                $masukan,
                $produk instanceof Product ? $produk->id : null,
            ),
        ]);
    }
}
