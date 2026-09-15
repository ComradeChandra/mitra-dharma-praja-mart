<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form "Lupa password" anggota (belum login).
 *
 * Anggotanya dipilih dari dropdown seperti di halaman masuk, dan wajib
 * anggota AKTIF: anggota nonaktif memang tidak bisa masuk, jadi password
 * baru pun tidak ada gunanya.
 */
class StorePasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code' => ['required', 'string', Rule::exists('members', 'member_code')->where('is_active', true)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_code.required' => 'Pilih nama kamu dulu.',
            'member_code.exists' => 'Nama ini tidak ditemukan atau akunnya sedang nonaktif. Hubungi pengurus koperasi.',
            'note.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
