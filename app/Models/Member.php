<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Model untuk tabel members.
 *
 * Anggota koperasi tidak mendaftar sendiri, datanya diinput admin dengan kode
 * unik (mis. "0010 A"), DAN admin yang sekaligus buatkan/set password-nya
 * (lihat CLAUDE.md, bagian Aktor). Anggota login pakai member_code dan
 * password itu.
 *
 * Extends Authenticatable (bukan Model biasa) supaya bisa dipakai login lewat
 * guard 'member' terpisah dari guard 'web' (admin), lihat config/auth.php.
 */
#[Fillable(['member_code', 'full_name', 'whatsapp_number', 'address', 'photo_path', 'password', 'is_active'])]
#[Hidden(['password'])]
class Member extends Authenticatable
{
    /**
     * Cast is_active jadi boolean asli, dan password otomatis di-hash
     * setiap kali diisi (baik lewat create() maupun update()), sama seperti
     * cast 'hashed' di model User bawaan Laravel.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Anggota berstatus aktif.
     *
     * Dijadikan scope karena syarat ini dipakai di dropdown login, hitungan
     * dashboard, dan rekap sudah/belum belanja. Kalau definisinya berubah,
     * cukup diubah di sini.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Cari berdasarkan nama atau kode anggota.
     *
     * Kata kunci kosong diabaikan, jadi pemanggilnya tidak perlu menulis if
     * sendiri. Pencocokannya pakai LIKE supaya mengetik sebagian nama tetap
     * ketemu, dan tidak peduli huruf besar-kecil.
     *
     * Dibutuhkan karena anggotanya nanti sekitar 100 orang, terbagi lima
     * halaman — mencari satu nama tanpa ini berarti membolak-balik halaman.
     */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($kata) {
            $q->where('full_name', 'like', "%{$kata}%")
                ->orWhere('member_code', 'like', "%{$kata}%");
        });
    }

    /**
     * Relasi: satu anggota bisa punya banyak riwayat pesanan.
     * Dipakai juga sebagai dasar hitung profil belanja tahunan/SHU.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relasi: satu anggota bisa mengajukan banyak permintaan produk baru.
     */
    public function productRequests(): HasMany
    {
        return $this->hasMany(ProductRequest::class);
    }
}
