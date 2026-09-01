<?php

namespace App\Enums;

/**
 * Status tinjauan admin atas permintaan produk baru yang diajukan
 * anggota/non-anggota.
 *
 * - Pending  : baru diajukan, belum ditinjau admin.
 * - Approved : admin setuju, produk akan ditambahkan ke katalog secara manual.
 * - Rejected : admin menolak permintaan.
 */
enum ProductRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Label yang enak dibaca manusia, dipakai buat ditampilkan di tampilan admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Warna badge (lihat komponen x-admin.badge) buat status ini, ditaruh di
     * sini (bukan diulang match() di tiap view) biar konsisten & DRY, sama
     * pola dengan OrderStatus::badgeColor(). Dipakai di 2 tempat: index admin
     * & index anggota (member/product-requests, admin/product-requests).
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
        };
    }
}
