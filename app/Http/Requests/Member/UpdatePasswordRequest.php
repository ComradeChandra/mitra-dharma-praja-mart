<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi form ganti password anggota.
 *
 * Wajib memasukkan password LAMA dulu, supaya orang lain yang kebetulan
 * sedang memegang HP/laptop anggota yang masih login tidak bisa mengganti
 * passwordnya tanpa tahu password aslinya.
 *
 * Rule 'current_password' harus ditunjuk ke guard 'member', bukan guard
 * default 'web', kalau tidak, Laravel akan mengecek ke akun admin yang
 * login, bukan ke anggota yang sedang membuka halaman ini.
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
            'current_password' => ['required', 'current_password:member'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Masukkan dulu password kamu yang sekarang.',
            'current_password.current_password' => 'Password lama yang kamu masukkan salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
