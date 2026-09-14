<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form edit produk yang sudah ada. Aturannya sama persis dengan
 * StoreProductRequest, bedanya cuma field 'image' di sini opsional total
 * (admin tidak wajib upload ulang foto tiap kali edit produk).
 */
class UpdateProductRequest extends FormRequest
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
            'sell_price' => ['nullable', 'numeric', 'min:0', 'max:100000000', 'required_unless:is_fluctuating,1'],
            'is_fluctuating' => ['nullable', 'boolean'],
            'has_stock_tracking' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000', 'required_if:has_stock_tracking,1'],
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
