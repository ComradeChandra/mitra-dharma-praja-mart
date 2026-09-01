<?php

namespace App\Http\Requests\NonMember;

use App\Models\OpdDepartment;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi + proses "login" non-anggota, dipisah namespace NonMember/ dari
 * Member/ (isolasi penuh, sesuai CLAUDE.md) karena ini bukan akun personal:
 * satu kode akses dipakai bersama oleh semua staf 1 OPD, bukan Auth::attempt()
 * ke 1 baris user tertentu. Makanya tidak pakai guard Laravel, cukup simpan
 * opd_department_id yang berhasil diverifikasi ke session (lihat
 * EnsureNonMemberSession middleware yang membaca session ini).
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
            'opd_department_id' => ['required', 'integer', 'exists:opd_departments,id'],
            'access_code' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'opd_department_id.required' => 'Pilih nama OPD dulu.',
            'opd_department_id.exists' => 'OPD yang dipilih tidak ditemukan.',
            'access_code.required' => 'Kode akses wajib diisi.',
        ];
    }

    /**
     * Cek kode akses OPD yang dipilih. Beda dari Member\LoginRequest (yang
     * pakai Auth::guard()->attempt()), di sini manual Hash::check() karena
     * OpdDepartment bukan model Authenticatable, dampaknya sama (rate
     * limited, pesan error seragam), cuma mekanismenya lebih sederhana sesuai
     * sifatnya yang bukan akun personal.
     *
     * @throws ValidationException
     */
    public function authenticate(): OpdDepartment
    {
        $this->ensureIsNotRateLimited();

        $opd = OpdDepartment::find($this->integer('opd_department_id'));

        if (! $opd || ! $opd->access_code || ! Hash::check($this->string('access_code'), $opd->access_code)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'access_code' => 'Kode akses salah untuk OPD yang dipilih.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $opd;
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
            'access_code' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate($this->input('opd_department_id').'|'.$this->ip());
    }
}
