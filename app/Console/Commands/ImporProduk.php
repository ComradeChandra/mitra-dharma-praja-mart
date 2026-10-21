<?php

namespace App\Console\Commands;

use App\Services\BerkasCsvService;
use App\Services\ImporProdukService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * php artisan koperasi:impor-produk <berkas.csv> --foto=<folder> [--simpan]
 *
 * Tanpa --simpan: hanya memeriksa dan menampilkan apa yang AKAN terjadi.
 * Dengan --simpan: produk baru disimpan, fotonya disalin ke storage.
 *
 * Logikanya di ImporProdukService; perintah ini cuma membaca berkas,
 * memanggil service, dan menampilkan hasil.
 */
class ImporProduk extends Command
{
    protected $signature = 'koperasi:impor-produk
        {berkas : Berkas CSV dengan kolom kategori, nama, harga_beli, harga_jual, fluktuatif, stok, foto}
        {--foto= : Folder tempat berkas foto yang disebut di kolom "foto"}
        {--simpan : Benar-benar menyimpan (tanpa ini cuma pemeriksaan)}';

    protected $description = 'Impor data produk koperasi sekaligus dari berkas CSV dan folder foto';

    public function handle(BerkasCsvService $csv, ImporProdukService $impor): int
    {
        // Baca berkas; kolom yang kurang atau berkas yang tidak ada langsung dihentikan
        try {
            $baris = $csv->baca($this->argument('berkas'), ImporProdukService::KOLOM);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Periksa semua baris dan tampilkan hasilnya
        $hasil = $impor->periksa($baris, $this->option('foto'));
        $this->table(
            ['Baris', 'Kategori', 'Nama', 'Harga jual', 'Stok', 'Foto', 'Status', 'Keterangan'],
            array_map(fn ($h) => [
                $h['baris'], $h['kategori'], $h['nama'],
                $h['data']['sell_price'] ?? '(fluktuatif)', $h['data']['stock'] ?? '-',
                $h['foto'] ? 'ada' : '-', $h['status'], $h['pesan'],
            ], $hasil),
        );
        $jumlah = array_count_values(array_column($hasil, 'status'));
        $galat = $jumlah[ImporProdukService::GALAT] ?? 0;
        $this->info('Baru: '.($jumlah[ImporProdukService::BARU] ?? 0).' | Dilewati (sudah ada): '.($jumlah[ImporProdukService::LEWATI] ?? 0)." | Galat: {$galat}");

        if (! $this->option('simpan')) {
            $this->comment('Ini baru pemeriksaan, belum ada yang disimpan. Jalankan ulang dengan --simpan untuk menyimpan.');

            return $galat ? self::FAILURE : self::SUCCESS;
        }

        // Jangan menyimpan sebagian: baris galat harus dibetulkan atau dihapus dulu
        if ($galat) {
            $this->error('Masih ada baris galat. Betulkan atau hapus baris itu dari berkas, lalu jalankan ulang.');

            return self::FAILURE;
        }

        $this->info($impor->simpan($hasil).' produk baru tersimpan.');

        return self::SUCCESS;
    }
}
