<?php

namespace App\Http\Requests\NonMember;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form permintaan produk dari non-anggota.
 *
 * Beda dari versi anggota: non-anggota juga wajib menuliskan namanya sendiri.
 * Anggota namanya sudah tersimpan di akunnya, sedangkan non-anggota cuma
 * "masuk" pakai kode akses OPD yang dipakai bersama sekantor, jadi tanpa
 * nama, pengurus tidak tahu usulan itu datang dari siapa.
 */
class StoreProductRequestRequest extends FormRequest
{
    /**
     * Boleh diakses siapa saja yang lolos middleware 'non-member.session'.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'requester_name.required' => 'Nama kamu wajib diisi biar pengurus tahu usulan ini dari siapa.',
            'product_name.required' => 'Nama produk yang diusulkan wajib diisi.',
        ];
    }
}
