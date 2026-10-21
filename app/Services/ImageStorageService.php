<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Simpan & hapus berkas gambar yang diunggah (foto produk, foto profil
 * anggota, bukti transfer).
 *
 * Dipusatkan di sini karena logikanya sama persis di beberapa tempat: simpan,
 * lalu hapus berkas lama supaya tidak menumpuk jadi sampah yang tidak pernah
 * dipakai lagi. Sebelumnya cuma ada di Admin\ProductController sebagai method
 * privat, begitu foto profil anggota ditambahkan, logika itu bakal disalin ke
 * controller kedua.
 *
 * SOAL DISK: bawaannya 'public', yang berkasnya bisa dibuka siapa pun yang
 * punya URL-nya. Itu tepat buat foto produk, tapi TIDAK buat berkas yang
 * memuat data pribadi. Bukti transfer memuat nama pemilik rekening, nomor
 * rekening, kadang saldo, jadi disimpan di disk 'local' yang privat lalu
 * disajikan lewat rute yang memeriksa izin dulu.
 */
class ImageStorageService
{
    /**
     * Simpan gambar yang diunggah, kembalikan path relatifnya
     * (mis. "member-photos/abc.jpg") buat disimpan ke kolom database.
     * Null kalau tidak ada berkas yang diunggah.
     *
     * @param  string  $folder  nama folder di dalam disk yang dipilih
     * @param  string  $disk  'public' kalau boleh dilihat siapa saja,
     *                        'local' kalau isinya data pribadi
     */
    public function store(?UploadedFile $image, string $folder, string $disk = 'public'): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->store($folder, $disk);
    }

    /**
     * Simpan gambar yang sudah ada di disk (bukan unggahan form), mis. foto
     * produk dari impor data. Nama berkasnya dibuat acak seperti store(),
     * jadi dua foto bernama sama tidak saling menimpa.
     */
    public function storeFromPath(string $path, string $folder, string $disk = 'public'): string
    {
        return Storage::disk($disk)->putFile($folder, new File($path));
    }

    /**
     * Hapus berkas gambar kalau memang ada. Aman dipanggil dengan null atau
     * dengan path yang berkasnya sudah tidak ada, tidak akan error.
     */
    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * Ganti gambar lama dengan yang baru: simpan yang baru, hapus yang lama,
     * lalu kembalikan path baru. Kalau tidak ada berkas baru diunggah, path
     * lama dipertahankan apa adanya (tidak ikut terhapus).
     */
    public function replace(?UploadedFile $baru, ?string $pathLama, string $folder, string $disk = 'public'): ?string
    {
        if (! $baru) {
            return $pathLama;
        }

        $this->delete($pathLama, $disk);

        return $this->store($baru, $folder, $disk);
    }
}
