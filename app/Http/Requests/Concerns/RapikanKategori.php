<?php

namespace App\Http\Requests\Concerns;

use App\Models\Product;
use App\Services\KategoriProdukService;

/**
 * Merapikan isian kategori SEBELUM divalidasi dan disimpan. Dipakai bareng
 * oleh StoreProductRequest dan UpdateProductRequest supaya aturannya cuma ada
 * di satu tempat (KategoriProdukService::rapikan).
 */
trait RapikanKategori
{
    protected function prepareForValidation(): void
    {
        // Isian aneh (array, dsb) dibiarkan apa adanya: aturan 'string' di
        // rules() yang akan menolaknya dengan pesan yang jelas.
        if (! is_string($this->input('category'))) {
            return;
        }

        // Saat mengubah produk, rute membawa produknya ({product}); produk itu
        // tidak ikut dibandingkan. Saat menambah produk, tidak ada.
        $produk = $this->route('product');

        $this->merge([
            'category' => app(KategoriProdukService::class)->rapikan(
                $this->input('category'),
                $produk instanceof Product ? $produk->id : null,
            ),
        ]);
    }
}
