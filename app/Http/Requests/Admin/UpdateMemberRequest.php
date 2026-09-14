<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesWhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form edit anggota yang sudah ada.
 */
class UpdateMemberRequest extends FormRequest
{
    use NormalizesWhatsAppNumber;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('members', 'member_code')->ignore($this->route('member')),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            // Password OPSIONAL di form edit, kosongkan kalau admin tidak mau
            // ganti password anggota ini. Kalau diisi, minimal 8 karakter
            // (dinaikkan dari 6 setelah review keamanan).
            'password' => ['nullable', 'string', 'min:8'],
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
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
