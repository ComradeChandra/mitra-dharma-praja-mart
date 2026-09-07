<?php

namespace App\Enums;

/**
 * Status pembayaran satu pesanan.
 *
 * SENGAJA terpisah dari OrderStatus. Keduanya perjalanan yang berbeda:
 * OrderStatus melacak perjalanan pesanannya (masuk, diverifikasi harganya,
 * invoice dikirim), sedangkan ini melacak perjalanan uangnya. Pesanan bisa
 * sudah terverifikasi tapi belum dibayar, dan sebaliknya.
 *
 * QRIS koperasi itu QRIS statis cetakan, bukan payment gateway. Uangnya masuk
 * langsung ke rekening koperasi dan aplikasi tidak pernah diberi tahu apa pun.
 * Karena itu perpindahan status di sini digerakkan manusia, bukan webhook:
 * pemesan menyatakan sudah bayar, pengurus mencocokkan ke mutasi lalu
 * mengonfirmasi. Alur ini yang disetujui Pak Emir sendiri (3 Sep 2026, lihat
 * Lampiran C di CLAUDE.md).
 */
enum PaymentStatus: string
{
    /** Belum ada pernyataan bayar dari pemesan. */
    case Unpaid = 'unpaid';

    /** Pemesan sudah menyatakan bayar, pengurus belum mencocokkan. */
    case AwaitingConfirmation = 'awaiting_confirmation';

    /** Pengurus sudah mencocokkan ke rekening. */
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::AwaitingConfirmation => 'Menunggu Konfirmasi',
            self::Paid => 'Lunas',
        };
    }

    /** Warna badge, dipakai bareng komponen x-admin.badge. */
    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::AwaitingConfirmation => 'amber',
            self::Paid => 'green',
        };
    }
}
