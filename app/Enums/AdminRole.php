<?php

namespace App\Enums;

/**
 * Peran akun di sisi pengurus (kolom users.role), 16 Sep 2026.
 *
 * Cuma dua, sesuai skala koperasi (±100 anggota):
 * - Admin Utama : semua yang bisa dilakukan Pengurus, ditambah mengelola akun
 *                 pengurus dan pengaturan koperasi (nomor WhatsApp).
 * - Pengurus    : semua pekerjaan harian: pesanan, pembayaran, produk,
 *                 anggota, OPD, periode, rekap, usulan produk, dan permintaan
 *                 lupa password.
 *
 * Yang membedakan keduanya dijaga Gate "admin-utama" (AppServiceProvider).
 */
enum AdminRole: string
{
    case AdminUtama = 'admin_utama';
    case Pengurus = 'pengurus';

    public function label(): string
    {
        return match ($this) {
            self::AdminUtama => 'Admin Utama',
            self::Pengurus => 'Pengurus',
        };
    }

    /** Satu kalimat penjelas, dipakai di form akun & daftar akun. */
    public function keterangan(): string
    {
        return match ($this) {
            self::AdminUtama => 'Akses penuh, termasuk menambah/menonaktifkan akun pengurus dan mengubah pengaturan koperasi.',
            self::Pengurus => 'Mengerjakan semua pekerjaan harian: pesanan, pembayaran, produk, anggota, periode, dan rekap.',
        };
    }

    /** Warna badge (lihat x-admin.badge). */
    public function color(): string
    {
        return match ($this) {
            self::AdminUtama => 'green',
            self::Pengurus => 'blue',
        };
    }
}
