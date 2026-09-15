<?php

namespace App\Enums;

/**
 * Siapa yang membatalkan sebuah pesanan (kolom orders.cancelled_by).
 *
 * Dicatat supaya pengurus bisa membedakan pesanan yang dibatalkan pemesannya
 * sendiri dari yang dibatalkan pengurus, misalnya karena barangnya habis.
 */
enum CancelledBy: string
{
    case Pemesan = 'pemesan';
    case Pengurus = 'pengurus';

    /**
     * Dipakai di kalimat "Dibatalkan oleh ...".
     */
    public function label(): string
    {
        return match ($this) {
            self::Pemesan => 'pemesan',
            self::Pengurus => 'pengurus',
        };
    }
}
