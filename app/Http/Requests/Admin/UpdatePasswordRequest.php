<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi form ganti password admin. Wajib masukkan password LAMA dulu
 * (dicek pakai rule bawaan Laravel 'current_password'), biar orang lain
 * yang kebetulan lagi pegang sesi admin yang masih login tidak bisa
 * sembarangan ganti password tanpa tau password aslinya.
 */
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // bail + string: isian berupa larik berhenti di sini, tidak sampai
            // ke pemeriksa password yang cuma menerima teks (error server).
            'current_password' => ['bail', 'required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Password lama yang kamu masukkan salah.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
