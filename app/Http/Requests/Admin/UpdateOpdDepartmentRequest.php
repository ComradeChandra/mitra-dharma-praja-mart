<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form edit OPD yang sudah ada.
 */
class UpdateOpdDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sama seperti StoreOpdDepartmentRequest, tapi cek unique-nya mengecualikan
     * data OPD yang sedang diedit sendiri (supaya nama yang tidak berubah tidak
     * dianggap "sudah dipakai"). Kode akses OPSIONAL di form edit, kosongkan
     * kalau admin tidak mau ganti kode akses OPD ini, sama seperti pola
     * password anggota di UpdateMemberRequest.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('opd_departments', 'name')->ignore($this->route('opd_department')),
            ],
            'access_code' => ['nullable', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama OPD wajib diisi.',
            'name.unique' => 'Nama OPD ini sudah ada di daftar.',
            'access_code.min' => 'Kode akses minimal 6 karakter.',
        ];
    }
}
