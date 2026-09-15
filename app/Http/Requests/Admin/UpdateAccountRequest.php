<?php

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form ubah akun pengurus.
 *
 * Satu aturan khusus: Admin Utama TIDAK bisa menurunkan perannya sendiri
 * atau menonaktifkan akunnya sendiri. Karena cuma Admin Utama aktif yang
 * bisa membuka halaman ini, aturan ini sekaligus menjamin koperasi selalu
 * punya minimal satu Admin Utama aktif, tidak mungkin terkunci di luar.
 */
class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User $akun */
        $akun = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($akun->id)],
            'role' => ['required', Rule::enum(AdminRole::class)],
            // Kosong = password tidak diganti
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $akun = $this->route('user');

                if (! $akun->is($this->user())) {
                    return;
                }

                if ($this->input('role') !== AdminRole::AdminUtama->value) {
                    $validator->errors()->add('role', 'Kamu tidak bisa menurunkan peran akunmu sendiri. Minta Admin Utama lain kalau memang perlu.');
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
