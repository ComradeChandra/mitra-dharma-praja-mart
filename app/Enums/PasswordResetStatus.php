<?php

namespace App\Enums;

/**
 * Status permintaan "lupa password" anggota (kolom password_reset_requests.status).
 */
enum PasswordResetStatus: string
{
    case Menunggu = 'menunggu';
    case Selesai = 'selesai';
    case Diabaikan = 'diabaikan';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Selesai => 'Password dibuatkan',
            self::Diabaikan => 'Diabaikan',
        };
    }

    /** Warna badge, mengikuti aturan warna status di CLAUDE.md. */
    public function color(): string
    {
        return match ($this) {
            self::Menunggu => 'amber',
            self::Selesai => 'green',
            self::Diabaikan => 'gray',
        };
    }
}
