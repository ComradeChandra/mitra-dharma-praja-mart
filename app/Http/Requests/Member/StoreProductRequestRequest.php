<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form pengajuan permintaan produk baru dari anggota.
 */
class StoreProductRequestRequest extends FormRequest
{
    /**
     * Boleh diakses siapa saja yang lolos middleware 'auth:member' di route
     * (yaitu anggota yang sedang login).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk yang diminta wajib diisi.',
        ];
    }
}
