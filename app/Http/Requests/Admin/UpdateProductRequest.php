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


            // Satuan opsional, mis. "renceng", "karung", "botol". Dikosongkan

            // berarti jumlah ditampilkan sebagai angka saja.

            'unit' => ['nullable', 'string', 'max:20'],
            'buy_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['nullable', 'numeric', 'min:0', 'required_unless:is_fluctuating,1'],
            'is_fluctuating' => ['nullable', 'boolean'],
            'has_stock_tracking' => ['nullable', 'boolean'],
            'stock' => ['nullable', 'integer', 'min:0', 'required_if:has_stock_tracking,1'],
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
