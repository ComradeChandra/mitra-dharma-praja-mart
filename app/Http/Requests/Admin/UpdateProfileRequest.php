<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form ganti nama & email admin (bukan password, itu form terpisah,
 * lihat UpdatePasswordRequest).
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Email unik, tapi kecualikan akun sendiri (kalau tidak, admin
            // akan selalu dianggap "bentrok" sama emailnya sendiri)
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ];
    }
}
