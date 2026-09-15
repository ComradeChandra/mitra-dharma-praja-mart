<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AdminRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun di sisi pengurus koperasi (guard 'web').
 *
 * Sejak 16 Sep 2026 punya peran (role): Admin Utama atau Pengurus, lihat
 * App\Enums\AdminRole. Akun yang dinonaktifkan (is_active = false) tidak
 * bisa masuk, dan yang sedang masuk ikut dikeluarkan (EnsureAdminIsActive).
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
            'is_active' => 'boolean',
        ];
    }

    /** Akun ini Admin Utama (boleh kelola akun pengurus & pengaturan). */
    public function adalahAdminUtama(): bool
    {
        return $this->role === AdminRole::AdminUtama;
    }

    /** Akun yang masih aktif. */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
