<?php

namespace App\Enums;

/**
 * Keputusan pengurus atas pesanan yang jumlahnya MELEBIHI stok tercatat
 * (17 Sep 2026). Hanya dipasang pada pesanan yang memang memuat barang
 * melebihi stok; pesanan lain nilainya null (tidak berlaku).
 *
 * Sistem ini pre-order: pesanan TIDAK pernah ditolak otomatis (lihat
 * OrderService::kurangiStok). Tapi supaya kelebihannya tidak lewat begitu
 * saja, pesanan yang melebihi stok ditandai "menunggu" dan muncul di dasbor
 * pengurus. Pengurus lalu memutuskan:
 *
 * - Setujui: koperasi akan belanja lebih untuk menutup kekurangannya, pesanan
 *   lanjut apa adanya.
 * - Tolak: barang yang kurang disesuaikan ke jumlah stok tercatat (kalau stok
 *   0, barangnya dihapus), stok kembali, total dihitung ulang, dan pemesan
 *   melihat catatannya.
 *
 * Aturan lengkapnya ada di OrderCancellationService.
 */
enum KeputusanStok: string
{
    /** Melebihi stok, pengurus belum memutuskan. */
    case Menunggu = 'menunggu';

    /** Pengurus setuju koperasi belanja lebih; pesanan lanjut. */
    case Disetujui = 'disetujui';

    /** Pengurus menolak; jumlah disesuaikan ke stok tercatat. */
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Perlu Tinjauan Stok',
            self::Disetujui => 'Stok Disetujui',
            self::Ditolak => 'Disesuaikan ke Stok',
        };
    }

    /** Warna badge, dipakai bareng komponen x-admin.badge. */
    public function color(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Disetujui => 'green',
            self::Ditolak => 'gray',
        };
    }
}
