<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form "Persentase SHU" di Admin -> Pengaturan.
 *
 * Dua angka: perkiraan terendah dan tertinggi. Kalau koperasi sudah menetapkan
 * satu angka pasti, keduanya diisi sama. Batas atas 100 supaya salah ketik
 * (mis. 1000) tidak tersimpan; SHU nyatanya kecil (kisaran 0,5-1%).
 */
class UpdateShuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shu_persen_min' => ['required', 'numeric', 'min:0', 'max:100', 'lte:shu_persen_maks'],
            'shu_persen_maks' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'shu_persen_min.required' => 'Isi perkiraan terendahnya.',
            'shu_persen_maks.required' => 'Isi perkiraan tertingginya.',
            'shu_persen_min.lte' => 'Perkiraan terendah tidak boleh lebih besar dari perkiraan tertinggi.',
            'shu_persen_min.max' => 'Persentase paling banyak 100.',
            'shu_persen_maks.max' => 'Persentase paling banyak 100.',
            'shu_persen_min.numeric' => 'Persentase harus berupa angka, mis. 0.5 atau 1.',
            'shu_persen_maks.numeric' => 'Persentase harus berupa angka, mis. 0.5 atau 1.',
        ];
    }
}
