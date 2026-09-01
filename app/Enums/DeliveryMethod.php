<?php

namespace App\Enums;

/**
 * Cara pemesan menerima barangnya: diantar ke alamat, atau diambil sendiri
 * di koperasi.
 *
 * Kalau "Antar", pesanan wajib punya alamat pengantaran
 * (`orders.delivery_address`). Kalau "Ambil", alamatnya dikosongkan, pemesan
 * datang sendiri ke koperasi, jadi tidak ada yang perlu dicatat.
 */
enum DeliveryMethod: string
{
    case Antar = 'antar';
    case Ambil = 'ambil';

    /**
     * Label yang enak dibaca manusia, dipakai di tampilan & teks invoice.
     */
    public function label(): string
    {
        return match ($this) {
            self::Antar => 'Diantar',
            self::Ambil => 'Ambil di koperasi',
        };
    }

    /**
     * Apakah cara ini butuh alamat pengantaran?
     * Dipusatkan di sini supaya aturan "kapan alamat wajib diisi" cuma
     * ditulis sekali, dipakai Form Request, Service, dan tampilan.
     */
    public function butuhAlamat(): bool
    {
        return $this === self::Antar;
    }
}
