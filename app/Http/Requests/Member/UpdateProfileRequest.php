<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\Concerns\NormalizesWhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form profil anggota.
 *
 * sengaja cuma nomor WhatsApp, alamat, dan foto. Nama lengkap & kode anggota
 * tidak ada di sini, keduanya tetap dikelola admin (lihat catatan di
 * Member\ProfileController). Kalau field-nya tidak ada di sini, dikirim
 * paksa lewat form pun tidak akan tersimpan.
 */
class UpdateProfileRequest extends FormRequest
{
    use NormalizesWhatsAppNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            // 2 MB, cukup buat foto dari kamera HP, tapi tidak bikin
            // penyimpanan cepat penuh.
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi (dipakai buat kirim invoice).',
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka (tanpa spasi/tanda +).',
            'photo.image' => 'Berkas yang diunggah harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * Cuma field yang boleh diubah anggota yang diteruskan ke model —
     * foto ditangani terpisah di controller karena perlu disimpan ke disk
     * dulu sebelum jadi path.
     */
    public function validated($key = null, $default = null): array
    {
        return [
            'whatsapp_number' => $this->string('whatsapp_number')->toString(),
            'address' => $this->string('address')->trim()->toString() ?: null,
        ];
    }
}
