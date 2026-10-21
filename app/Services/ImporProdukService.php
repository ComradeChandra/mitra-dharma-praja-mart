<?php

namespace App\Services;

use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Impor data produk sekaligus dari CSV + folder foto (21 Okt 2026).
 *
 * Pasangan ImporAnggotaService; dijalankan lewat
 * `php artisan koperasi:impor-produk`.
 *
 * Kolom CSV: kategori, nama, harga_beli, harga_jual, fluktuatif, stok, foto
 * - fluktuatif: "ya" / "tidak". Produk fluktuatif boleh tanpa harga jual
 *   (diisi pengurus saat verifikasi pesanan, mis. telur).
 * - stok: kosong = stok tidak dilacak (murni pre-order).
 * - foto: nama berkas di folder foto (boleh kosong = tanpa foto).
 *
 * Aturan sama seperti ImporAnggotaService: divalidasi dengan aturan form
 * Tambah Produk (StoreProductRequest, termasuk aturan foto JPG/PNG maks 2 MB),
 * nama yang sudah ada dilewati, dan penyimpanan semua-atau-tidak-sama-sekali.
 * Kategori dirapikan dengan KategoriProdukService, sama seperti di form.
 */
class ImporProdukService
{
    public const KOLOM = ['kategori', 'nama', 'harga_beli', 'harga_jual', 'fluktuatif', 'stok', 'foto'];

    public const BARU = 'baru';

    public const LEWATI = 'lewati';

    public const GALAT = 'galat';

    public function __construct(
        private KategoriProdukService $kategori,
        private ImageStorageService $penyimpanFoto,
    ) {}

    /**
     * Periksa semua baris TANPA menyimpan apa pun.
     *
     * @param  list<array<string, string|int>>  $baris  hasil BerkasCsvService::baca()
     * @param  string|null  $folderFoto  folder tempat berkas foto di kolom "foto"
     * @return list<array{baris: int, nama: string, kategori: string, status: string, pesan: string, data: array<string, mixed>, foto: string|null}>
     */
    public function periksa(array $baris, ?string $folderFoto): array
    {
        // Aturan form Tambah Produk; status aktif tidak ikut (produk impor selalu aktif)
        $form = new StoreProductRequest;
        $aturan = Arr::except($form->rules(), ['is_active']);

        $namaDiBerkas = [];
        $hasil = [];

        foreach ($baris as $b) {
            $foto = $b['foto'] !== '' ? rtrim((string) $folderFoto, '/\\').DIRECTORY_SEPARATOR.$b['foto'] : null;
            // Checkbox di form terkirim sebagai "1"/"0"; dibuat sama supaya aturan
            // required_unless / required_if di StoreProductRequest bekerja apa adanya
            $data = [
                'category' => $this->kategori->rapikan($b['kategori']),
                'name' => trim(preg_replace('/\s+/u', ' ', $b['nama'])),
                'buy_price' => $b['harga_beli'],
                'sell_price' => $b['harga_jual'] !== '' ? $b['harga_jual'] : null,
                'is_fluctuating' => in_array(mb_strtolower($b['fluktuatif']), ['ya', '1', 'true'], true) ? '1' : '0',
                'has_stock_tracking' => $b['stok'] !== '' ? '1' : '0',
                'stock' => $b['stok'] !== '' ? $b['stok'] : null,
            ];

            $hasil[] = ['baris' => $b['baris'], 'nama' => $data['name'], 'kategori' => $data['category'], 'data' => $data, 'foto' => $foto]
                + $this->nilai($data, $foto, $aturan, $form->messages(), $namaDiBerkas);
            $namaDiBerkas[] = mb_strtolower($data['name']);
        }

        return $hasil;
    }

    /**
     * Simpan baris berstatus BARU beserta fotonya. Semua atau tidak sama sekali.
     *
     * @param  list<array{status: string, data: array<string, mixed>, foto: string|null}>  $hasilPeriksa
     * @return int jumlah produk yang tersimpan
     */
    public function simpan(array $hasilPeriksa): int
    {
        $fotoTersimpan = [];

        try {
            return DB::transaction(function () use ($hasilPeriksa, &$fotoTersimpan) {
                $jumlah = 0;

                foreach ($hasilPeriksa as $h) {
                    if ($h['status'] !== self::BARU) {
                        continue;
                    }
                    $path = $h['foto'] ? $this->penyimpanFoto->storeFromPath($h['foto'], 'products') : null;
                    if ($path) {
                        $fotoTersimpan[] = $path;
                    }

                    Product::create([
                        ...$h['data'],
                        'is_fluctuating' => $h['data']['is_fluctuating'] === '1',
                        'has_stock_tracking' => $h['data']['has_stock_tracking'] === '1',
                        'is_active' => true,
                        'image_path' => $path,
                    ]);
                    $jumlah++;
                }

                return $jumlah;
            });
        } catch (\Throwable $e) {
            // Database dibatalkan, jadi foto yang sudah tersalin ikut dibuang
            // supaya tidak tertinggal jadi berkas yatim di storage
            foreach ($fotoTersimpan as $path) {
                $this->penyimpanFoto->delete($path);
            }
            throw $e;
        }
    }

    /**
     * Tentukan status satu baris: sudah ada (lewati), dobel di berkas, foto
     * tidak ditemukan, tidak lolos aturan form (galat), atau siap (baru).
     *
     * @return array{status: string, pesan: string}
     */
    private function nilai(array $data, ?string $foto, array $aturan, array $pesan, array $namaDiBerkas): array
    {
        if (Product::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists()) {
            return ['status' => self::LEWATI, 'pesan' => 'Produk dengan nama ini sudah ada di aplikasi.'];
        }

        if (in_array(mb_strtolower($data['name']), $namaDiBerkas, true)) {
            return ['status' => self::GALAT, 'pesan' => 'Nama produk ini muncul lebih dari sekali di berkas.'];
        }

        if ($foto && ! is_file($foto)) {
            return ['status' => self::GALAT, 'pesan' => 'Berkas foto tidak ditemukan: '.basename($foto)];
        }

        // Foto diperiksa dengan aturan form yang sama (JPG/PNG, maks 2 MB)
        $isi = $data + ['image' => $foto ? new UploadedFile($foto, basename($foto), null, null, true) : null];
        $validasi = Validator::make($isi, $aturan, $pesan);
        if ($validasi->fails()) {
            return ['status' => self::GALAT, 'pesan' => implode(' ', $validasi->errors()->all())];
        }

        return ['status' => self::BARU, 'pesan' => ''];
    }
}
