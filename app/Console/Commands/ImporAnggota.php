<?php

namespace App\Console\Commands;

use App\Services\BerkasCsvService;
use App\Services\ImporAnggotaService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * php artisan koperasi:impor-anggota <berkas.csv> [--simpan]
 *
 * Tanpa --simpan: hanya memeriksa dan menampilkan apa yang AKAN terjadi.
 * Dengan --simpan: anggota baru disimpan dan daftar password acaknya ditulis
 * ke berkas CSV terpisah untuk diserahkan ke pengurus.
 *
 * Logikanya di ImporAnggotaService; perintah ini cuma membaca berkas,
 * memanggil service, dan menampilkan hasil.
 */
class ImporAnggota extends Command
{
    protected $signature = 'koperasi:impor-anggota
        {berkas : Berkas CSV dengan kolom kode, nama, whatsapp, alamat}
        {--simpan : Benar-benar menyimpan (tanpa ini cuma pemeriksaan)}
        {--daftar-password= : Tempat menulis daftar password (bawaan: di sebelah berkas CSV)}';

    protected $description = 'Impor data anggota koperasi sekaligus dari berkas CSV';

    public function handle(BerkasCsvService $csv, ImporAnggotaService $impor): int
    {
        // Baca berkas; kolom yang kurang atau berkas yang tidak ada langsung dihentikan
        try {
            $baris = $csv->baca($this->argument('berkas'), ImporAnggotaService::KOLOM);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Periksa semua baris dan tampilkan hasilnya
        $hasil = $impor->periksa($baris);
        $this->table(
            ['Baris', 'Kode', 'Nama', 'Status', 'Keterangan'],
            array_map(fn ($h) => [$h['baris'], $h['kode'], $h['nama'], $h['status'], $h['pesan']], $hasil),
        );
        $jumlah = array_count_values(array_column($hasil, 'status'));
        $baru = $jumlah[ImporAnggotaService::BARU] ?? 0;
        $galat = $jumlah[ImporAnggotaService::GALAT] ?? 0;
        $this->info("Baru: {$baru} | Dilewati (sudah ada): ".($jumlah[ImporAnggotaService::LEWATI] ?? 0)." | Galat: {$galat}");

        if (! $this->option('simpan')) {
            $this->comment('Ini baru pemeriksaan, belum ada yang disimpan. Jalankan ulang dengan --simpan untuk menyimpan.');

            return $galat ? self::FAILURE : self::SUCCESS;
        }

        // Jangan menyimpan sebagian: baris galat harus dibetulkan atau dihapus dulu
        if ($galat) {
            $this->error('Masih ada baris galat. Betulkan atau hapus baris itu dari berkas, lalu jalankan ulang.');

            return self::FAILURE;
        }

        $daftarPassword = $impor->simpan($hasil);
        $this->info(count($daftarPassword).' anggota baru tersimpan.');

        if ($daftarPassword) {
            $tujuan = $this->option('daftar-password')
                ?: dirname($this->argument('berkas')).DIRECTORY_SEPARATOR.'daftar-password-anggota-'.now()->format('Ymd-His').'.csv';
            $this->tulisDaftarPassword($tujuan, $daftarPassword);
            $this->warn("Daftar password ditulis ke: {$tujuan}");
            $this->warn('Serahkan ke pengurus secara pribadi (bukan di grup), lalu HAPUS berkas itu dari server.');
        }

        return self::SUCCESS;
    }

    /** Tulis daftar password ke CSV yang hanya bisa dibaca pemilik akun server. */
    private function tulisDaftarPassword(string $tujuan, array $daftarPassword): void
    {
        $berkas = fopen($tujuan, 'w');
        fputcsv($berkas, ['kode', 'nama', 'whatsapp', 'password'], escape: '');
        foreach ($daftarPassword as $p) {
            fputcsv($berkas, [$p['kode'], $p['nama'], $p['whatsapp'], $p['password']], escape: '');
        }
        fclose($berkas);
        @chmod($tujuan, 0600);
    }
}
