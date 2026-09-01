<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form tambah OPD baru.
 */
class StoreOpdDepartmentRequest extends FormRequest
{
    /**
     * Boleh diakses siapa saja yang lolos middleware 'auth' di route (yaitu admin —
     * satu-satunya aktor yang login pakai email/password di project ini).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi: nama OPD wajib diisi, teks, maksimal 255 karakter,
     * dan harus unik (tidak boleh ada nama OPD yang sama persis). Kode akses
     * wajib diisi admin saat OPD baru dibuat, tanpa ini non-anggota dari
     * OPD tersebut tidak bisa login sama sekali (lihat CLAUDE.md, Aktor).
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:opd_departments,name'],
            'access_code' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * Pesan error custom berbahasa Indonesia biar gampang dipahami admin.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama OPD wajib diisi.',
            'name.unique' => 'Nama OPD ini sudah ada di daftar.',
            'access_code.required' => 'Kode akses wajib diisi — ini yang dipakai non-anggota dari OPD ini buat login.',
            'access_code.min' => 'Kode akses minimal 6 karakter.',
        ];
    }
}
