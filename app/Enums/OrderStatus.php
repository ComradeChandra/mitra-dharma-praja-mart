<?php

namespace App\Enums;

/**
 * Status alur satu pesanan (order), mengikuti proses pre-order koperasi:
 *
 * 1. Pending   : pesanan baru dikirim anggota/non-anggota, belum diproses admin.
 * 2. Verified  : admin sudah mengecek pesanan & mengunci harga (khusus produk
 *                fluktuatif seperti telur/sayur), total_amount sudah pasti.
 * 3. Invoiced  : invoice teks sudah dikirim admin ke WhatsApp pemesan.
 *
 * Cancelled bisa dicapai dari status mana pun (15 Sep 2026): pesanan tetap
 * tercatat, tapi tidak lagi dihitung di rekap, grafik, maupun SHU. Aturan
 * siapa boleh membatalkan dan kapan ada di OrderCancellationService.
 *
 * Status pembayaran QRIS terpisah di PaymentStatus, karena perjalanan uang
 * dan perjalanan pesanan bergerak sendiri-sendiri.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';

    /**
     * Label yang enak dibaca manusia, dipakai buat ditampilkan di tampilan admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Verified => 'Terverifikasi',
            self::Invoiced => 'Invoice Terkirim',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Warna badge (lihat komponen x-admin.badge) buat status ini, ditaruh di
     * sini (bukan diulang di tiap view pakai match()) biar konsisten di semua
     * halaman yang nampilin status pesanan (index & detail, admin & anggota).
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Verified => 'green',
            self::Invoiced => 'blue',
            self::Cancelled => 'gray',
        };
    }

    /**
     * Harganya sudah pasti dan pesanannya masih berlaku: boleh dibuatkan
     * invoice, dibagikan struknya, dan dihitung di rekap.
     *
     * Dulu tempat-tempat itu cukup mengecek "bukan pending". Sejak ada status
     * Dibatalkan, cara itu ikut meloloskan pesanan yang sudah batal.
     */
    public function hargaSudahFinal(): bool
    {
        return $this === self::Verified || $this === self::Invoiced;
    }
}
