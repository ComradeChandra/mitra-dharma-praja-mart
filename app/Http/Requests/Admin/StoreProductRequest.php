<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form tambah produk baru ke katalog.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],


            // Satuan opsional, mis. "renceng", "karung", "botol". Dikosongkan

            // berarti jumlah ditampilkan sebagai angka saja.

            'unit' => ['nullable', 'string', 'max:20'],
            'buy_price' => ['required', 'numeric', 'min:0'],

            // sell_price wajib diisi kecuali produknya ditandai fluktuatif
            // (is_fluctuating dicentang), harga produk fluktuatif baru diisi
            // admin nanti saat verifikasi pesanan, bukan saat produk dibuat.
            'sell_price' => ['nullable', 'numeric', 'min:0', 'required_unless:is_fluctuating,1'],
            'is_fluctuating' => ['nullable', 'boolean'],

            // stock wajib diisi kalau has_stock_tracking dicentang (produk ini
            // melacak stok). Kalau tidak dicentang, stok memang sengaja kosong
            // karena sifatnya opsional per produk (lihat CLAUDE.md, Konsep Inti).
            'has_stock_tracking' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0', 'required_if:has_stock_tracking,1'],

            // Foto produk opsional, boleh dari screenshot JPG/PNG, maksimal 2MB
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori produk wajib diisi.',
            'name.required' => 'Nama produk wajib diisi.',
            'buy_price.required' => 'Harga beli wajib diisi.',
            'sell_price.required_unless' => 'Harga jual wajib diisi untuk produk yang harganya tidak fluktuatif.',
            'stock.required_if' => 'Jumlah stok wajib diisi kalau pelacakan stok diaktifkan.',
            'image.image' => 'File foto harus berupa gambar.',
            'image.mimes' => 'Foto harus berformat JPG atau PNG.',
            'image.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
