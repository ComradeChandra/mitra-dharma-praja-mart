<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Sumber tunggal persentase SHU (16 Sep 2026).
 *
 * Admin Utama mengaturnya di Admin -> Pengaturan; nilainya disimpan di tabel
 * settings. Selama belum diisi dari aplikasi, dipakai nilai bawaan di
 * config/koperasi.php (di rapat disebut kisaran 0,5%-1%).
 *
 * SEMUA pembacaan persentase SHU harus lewat sini, jangan membaca config atau
 * tabel settings langsung dari controller/Blade — supaya angkanya tidak
 * tersebar di banyak tempat seperti dulu (lihat catatan di RecapService).
 */
class ShuService
{
    /**
     * Persentase perkiraan SHU: batas bawah dan atas.
     *
     * Kalau min == maks, artinya persentasenya sudah pasti (bukan rentang lagi).
     *
     * @return array{min: float, maks: float}
     */
    public function persentase(): array
    {
        $pengaturan = Setting::query()->first();

        return [
            'min' => $this->nilai($pengaturan?->shu_persen_min, 'koperasi.shu.persen_min'),
            'maks' => $this->nilai($pengaturan?->shu_persen_maks, 'koperasi.shu.persen_maks'),
        ];
    }

    /**
     * Simpan persentase baru dari halaman Pengaturan.
     */
    public function simpan(float $min, float $maks): void
    {
        $pengaturan = Setting::query()->first() ?? new Setting;
        $pengaturan->fill(['shu_persen_min' => $min, 'shu_persen_maks' => $maks])->save();
    }

    /**
     * Persentase sudah diisi dari aplikasi (bukan lagi memakai bawaan config).
     * Dipakai halaman Pengaturan buat memberi tahu statusnya.
     */
    public function sudahDiaturDariAplikasi(): bool
    {
        return Setting::query()->whereNotNull('shu_persen_min')->exists();
    }

    /** Nilai dari tabel settings kalau ada; kalau null, pakai bawaan config. */
    private function nilai(int|float|string|null $tersimpan, string $kunciConfig): float
    {
        return $tersimpan !== null ? (float) $tersimpan : (float) config($kunciConfig);
    }
}
