<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesWhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form Admin -> Pengaturan (nomor WhatsApp koperasi).
 *
 * Nomornya boleh dikosongkan: artinya tombol "Hubungi Pengurus" disembunyikan.
 * Spasi, strip, dan tanda + dibuang dulu oleh NormalizesWhatsAppNumber, jadi
 * "0812-3456-7890" dan "+62 812 3456 7890" sama-sama diterima.
 */
class UpdateSettingRequest extends FormRequest
{
    use NormalizesWhatsAppNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Lebih ketat dari nomor anggota: nomor ini dipasang di tombol
            // yang dilihat semua pemesan, jadi nomor yang jelas salah
            // (mis. "12345") jangan sampai tersimpan. Diawali 0 atau 62,
            // lalu 8 sampai 13 angka.
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^(0|62)[0-9]{8,13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'whatsapp_number.regex' => 'Isi nomor WhatsApp yang diawali 08 atau 62, contoh 081234567890.',
            'whatsapp_number.max' => 'Nomor WhatsApp terlalu panjang.',
        ];
    }
}
