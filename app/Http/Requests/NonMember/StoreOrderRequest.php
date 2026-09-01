<?php

namespace App\Http\Requests\NonMember;

use App\Http\Requests\Concerns\ValidatesOrderQuantities;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form pemesanan non-anggota. Sama seperti Member\StoreOrderRequest
 * (field quantity-nya dipakai bareng lewat trait ValidatesOrderQuantities),
 * TAPI non-anggota juga wajib isi nama & nomor WA sendiri di form, beda dari
 * anggota yang datanya sudah ada di akun (member_code, whatsapp_number),
 * non-anggota cuma "login" pakai kode akses OPD bersama, jadi identitas orang
 * per pesanannya harus diketik manual di form ini.
 */
class StoreOrderRequest extends FormRequest
{
    use ValidatesOrderQuantities;

    /**
     * Boleh diakses siapa saja yang lolos middleware 'non-member.session' di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->quantityRules(),
            ...$this->deliveryRules(),
            'non_member_name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->quantityMessages(),
            ...$this->deliveryMessages(),
            'non_member_name.required' => 'Nama kamu wajib diisi.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi (buat kirim invoice nanti).',
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka (tanpa spasi/tanda +).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateQuantities($validator));
    }
}
