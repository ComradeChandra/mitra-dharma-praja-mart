<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah akun pengurus (Admin -> Akun Pengurus).
 * Rutenya sudah dijaga Gate "admin-utama", jadi authorize() cukup true.
 */
class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(AdminRole::class)],
            // Password awal dibuatkan Admin Utama, sama seperti password
            // anggota dibuatkan pengurus. Pemiliknya bisa menggantinya sendiri
            // di Profil Saya.
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'role.required' => 'Pilih peran akun ini.',
            'password.required' => 'Password awal wajib diisi, pemilik akun masuk memakai password ini.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
