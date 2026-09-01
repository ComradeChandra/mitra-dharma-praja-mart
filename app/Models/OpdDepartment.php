<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel opd_departments.
 *
 * Menyimpan daftar OPD (Organisasi Perangkat Daerah) Pemkot Cimahi yang dipilih
 * non-anggota dari dropdown saat login/memesan, non-anggota tidak boleh
 * mengetik nama instansi manual (lihat CLAUDE.md, Konsep Inti).
 *
 * Kolom access_code = kode akses BERSAMA per OPD (bukan akun personal per
 * orang) buat non-anggota masuk, lihat CLAUDE.md bagian Aktor,
 * 24 Agustus 2026 (lihat CLAUDE.md, Lampiran B).
 */
#[Fillable(['name', 'access_code'])]
#[Hidden(['access_code'])]
class OpdDepartment extends Model
{
    /**
     * access_code di-hash otomatis kayak password anggota, walau ini kode
     * bersama (bukan akun personal), tetap kredensial yang sebaiknya tidak
     * tersimpan polos di database.
     */
    protected function casts(): array
    {
        return [
            'access_code' => 'hashed',
        ];
    }

    /**
     * Relasi: satu OPD bisa punya banyak pesanan (dari non-anggota yang memilih OPD ini).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
