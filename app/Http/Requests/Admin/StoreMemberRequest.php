<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesWhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form tambah anggota baru. Anggota tidak mendaftar sendiri —
 * ini yang dipakai admin buat input data anggota (lihat CLAUDE.md, Aktor).
 */
class StoreMemberRequest extends FormRequest
{
    use NormalizesWhatsAppNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Kode unik anggota, contoh: "0010 A". Wajib unik di seluruh anggota.
            'member_code' => ['required', 'string', 'max:50', 'unique:members,member_code'],
            'full_name' => ['required', 'string', 'max:255'],
            // Nomor WhatsApp dipakai kirim invoice, jadi wajib diisi angka saja
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            // Password anggota wajib diisi admin saat anggota baru dibuat —
            // anggota tidak bikin akun sendiri (lihat CLAUDE.md, "Perubahan
            // Requirement"). Minimal 8 karakter (standar keamanan wajar),
            // dinaikkan dari 6 setelah review keamanan.
            'password' => ['required', 'string', 'min:8'],
            // Checkbox aktif/nonaktif, kalau tidak dicentang, browser tidak
            // mengirim field ini sama sekali, makanya pakai 'boolean' + default di controller
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_code.required' => 'Kode anggota wajib diisi.',
            'member_code.unique' => 'Kode anggota ini sudah dipakai anggota lain.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka (tanpa spasi/tanda +).',
            'password.required' => 'Password wajib diisi — anggota login pakai password ini.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
