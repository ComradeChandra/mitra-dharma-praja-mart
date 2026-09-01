<?php

namespace App\Enums;

/**
 * Tipe pemesan/peminta produk: anggota koperasi atau bukan anggota.
 *
 * Dipakai di tabel `orders` dan `product_requests` untuk membedakan alur data
 * (anggota terhubung ke tabel members, non-anggota mengetik nama sendiri +
 * wajib pilih OPD dari dropdown).
 */
enum UserType: string
{
    case Member = 'member';
    case NonMember = 'non_member';

    /**
     * Label yang enak dibaca manusia, dipakai buat ditampilkan di tampilan admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Member => 'Anggota',
            self::NonMember => 'Non-Anggota',
        };
    }
}
