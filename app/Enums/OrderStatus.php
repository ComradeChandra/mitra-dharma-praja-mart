<?php

namespace App\Enums;

/**
 * Status alur satu pesanan (order), mengikuti proses pre-order koperasi:
 *
 * 1. Pending  : pesanan baru dikirim anggota/non-anggota, belum diproses admin.
 * 2. Verified : admin sudah mengecek pesanan & mengunci harga (khusus produk
 *               fluktuatif seperti telur/sayur), total_amount sudah pasti.
 * 3. Invoiced : invoice teks sudah dikirim admin ke WhatsApp pemesan.
 *
 * Alurnya berhenti di "invoiced". Tidak ada status pembayaran atau lunas,
 * karena aplikasi ini bukan e-commerce (lihat CLAUDE.md, Konsep Inti).
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Invoiced = 'invoiced';

    /**
     * Label yang enak dibaca manusia, dipakai buat ditampilkan di tampilan admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Verified => 'Terverifikasi',
            self::Invoiced => 'Invoice Terkirim',
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
        };
    }
}
