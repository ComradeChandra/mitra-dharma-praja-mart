<?php

namespace App\Enums;

/**
 * Status periode pemesanan.
 *
 * "Open"   = periode sedang dibuka admin, anggota/non-anggota bisa mengirim pesanan.
 * "Closed" = periode sudah ditutup, tidak menerima pesanan baru lagi.
 *
 * Enum ini string-backed (punya nilai string di belakangnya) supaya nilainya
 * yang tersimpan di kolom `order_periods.status` tetap gampang dibaca langsung
 * di database (bukan angka 0/1 yang harus dihafal artinya).
 */
enum OrderPeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    /**
     * Label yang enak dibaca manusia, dipakai buat ditampilkan di tampilan admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
        };
    }
}
