<?php

namespace App\Services;

use RuntimeException;

/**
 * Pembaca berkas CSV untuk impor data (anggota & produk).
 *
 * Baris pertama = nama kolom. Setiap baris berikutnya dikembalikan sebagai
 * array ['nama_kolom' => 'isi'], plus 'baris' = nomor baris di berkas supaya
 * pesan kesalahan bisa menunjuk baris yang tepat.
 *
 * Toleran terhadap hal-hal kecil yang biasa muncul dari Excel / Google Sheets:
 * tanda BOM di awal berkas, spasi di sekitar nama kolom, dan baris kosong.
 */
class BerkasCsvService
{
    /**
     * @param  list<string>  $kolomWajib  nama kolom yang harus ada di baris judul
     * @return list<array<string, string|int>>
     */
    public function baca(string $path, array $kolomWajib): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Berkas tidak ditemukan: {$path}");
        }

        $berkas = fopen($path, 'r');

        // Baris judul: buang BOM UTF-8 dan rapikan nama kolom
        $judul = fgetcsv($berkas, escape: '') ?: [];
        $judul = array_map(fn ($nama) => strtolower(trim(str_replace("\u{FEFF}", '', (string) $nama))), $judul);

        $kurang = array_diff($kolomWajib, $judul);
        if ($kurang) {
            fclose($berkas);
            throw new RuntimeException('Kolom berikut tidak ada di baris judul: '.implode(', ', $kurang));
        }

        // Isi: baris 2 dan seterusnya (nomor baris mengikuti tampilan di Excel)
        $hasil = [];
        $nomor = 1;
        while (($isi = fgetcsv($berkas, escape: '')) !== false) {
            $nomor++;
            if ($isi === [null] || trim(implode('', $isi)) === '') {
                continue;
            }
            $baris = ['baris' => $nomor];
            foreach ($judul as $i => $nama) {
                $baris[$nama] = trim((string) ($isi[$i] ?? ''));
            }
            $hasil[] = $baris;
        }

        fclose($berkas);

        return $hasil;
    }
}
