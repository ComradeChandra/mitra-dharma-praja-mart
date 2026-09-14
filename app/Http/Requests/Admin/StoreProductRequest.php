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
            'buy_price' => ['required', 'numeric', 'min:0', 'max:100000000'],

            // sell_price wajib diisi kecuali produknya ditandai fluktuatif
            // (is_fluctuating dicentang), harga produk fluktuatif baru diisi
            // admin nanti saat verifikasi pesanan, bukan saat produk dibuat.
            'sell_price' => ['nullable', 'numeric', 'min:0', 'max:100000000', 'required_unless:is_fluctuating,1'],
            'is_fluctuating' => ['nullable', 'boolean'],

            // stock wajib diisi kalau has_stock_tracking dicentang (produk ini
            // melacak stok). Kalau tidak dicentang, stok memang sengaja kosong
            // karena sifatnya opsional per produk (lihat CLAUDE.md, Konsep Inti).
            'has_stock_tracking' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000', 'required_if:has_stock_tracking,1'],

            // Foto produk opsional, boleh dari screenshot JPG/PNG, maksimal 2MB
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            // Batas atas: tanpa ini, kelebihan satu-dua angka nol berakhir di
            // error server, karena kolom harga di database maksimal Rp9,99 miliar.
            'buy_price.max' => 'Harga beli maksimal Rp100.000.000. Cek lagi, mungkin kelebihan angka nol.',
            'sell_price.max' => 'Harga jual maksimal Rp100.000.000. Cek lagi, mungkin kelebihan angka nol.',
            'stock.max' => 'Stok maksimal 1.000.000. Cek lagi, mungkin kelebihan angka nol.',
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
