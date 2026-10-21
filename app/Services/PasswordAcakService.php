<?php

namespace App\Services;

/**
 * Pembuat password acak untuk anggota.
 *
 * Dipakai di dua tempat: antrean lupa password (PasswordResetService) dan
 * impor data anggota (ImporAnggotaService). Sebelumnya cuma ada sebagai
 * method privat di PasswordResetService; dipindah ke sini supaya impor
 * memakai aturan yang sama persis, bukan salinan.
 */
class PasswordAcakService
{
    /**
     * Huruf & angka untuk password acak. Tanpa yang mirip satu sama lain
     * (0/o, 1/l/i), karena anggota mengetiknya ulang dari WhatsApp.
     */
    private const HURUF = 'abcdefghjkmnpqrstuvwxyz23456789';

    /** Password 8 karakter acak dari HURUF, memakai random_int (acak kriptografis). */
    public function buat(): string
    {
        $hasil = '';
        for ($i = 0; $i < 8; $i++) {
            $hasil .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
        }

        return $hasil;
    }
}
