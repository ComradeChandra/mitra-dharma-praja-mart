<?php

namespace App\Http\Requests\Member;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi + proses login anggota. Polanya sama seperti LoginRequest admin
 * (App\Http\Requests\Auth\LoginRequest, bawaan Breeze), termasuk rate
 * limiting biar tidak gampang di-brute-force, tapi pakai guard 'member'
 * dan kolom 'member_code' (bukan email) sebagai identitas login.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_code.required' => 'Kode anggota wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Coba login pakai guard 'member'. is_active ikut disertakan sebagai
     * syarat query, anggota yang dinonaktifkan admin otomatis tidak bisa
     * login meski password-nya benar.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'member_code' => $this->string('member_code'),
            'password' => $this->string('password'),
            'is_active' => true,
        ];

        if (! Auth::guard('member')->attempt($credentials)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'member_code' => 'Kode anggota atau password salah, atau akun kamu sedang dinonaktifkan.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'member_code' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('member_code')).'|'.$this->ip());
    }
}
