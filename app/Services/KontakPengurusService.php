<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Nomor WhatsApp koperasi yang bisa dihubungi anggota & non-anggota
 * (16 Sep 2026).
 *
 * Nomornya diisi pengurus sendiri lewat Admin -> Pengaturan, disimpan di
 * tabel settings (satu baris). Kalau belum diisi, tombol "Hubungi Pengurus"
 * tidak ditampilkan di mana pun.
 */
class KontakPengurusService
{
    public function __construct(private WhatsAppLinkService $tautanWhatsApp) {}

    /** Nomor yang tersimpan (angka saja, mis. 081234567890), atau null. */
    public function nomorWhatsApp(): ?string
    {
        return Setting::query()->value('whatsapp_number');
    }

    /** Simpan nomor baru. Null berarti tombol WhatsApp disembunyikan. */
    public function simpanNomorWhatsApp(?string $nomor): void
    {
        $pengaturan = Setting::query()->first() ?? new Setting;
        $pengaturan->fill(['whatsapp_number' => $nomor])->save();
    }

    /**
     * Tautan wa.me ke nomor koperasi dengan pesan pembuka yang sudah terisi.
     * Null kalau nomornya belum diisi pengurus.
     */
    public function tautanWhatsApp(string $pesan = ''): ?string
    {
        $nomor = $this->nomorWhatsApp();

        return $nomor === null ? null : $this->tautanWhatsApp->keNomor($nomor, $pesan);
    }
}
