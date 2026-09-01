<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Simpan & hapus berkas gambar yang diunggah (foto produk, foto profil
 * anggota).
 *
 * Dipusatkan di sini karena logikanya sama persis di beberapa tempat:
 * simpan ke disk 'public', dan hapus berkas lama supaya tidak menumpuk jadi
 * sampah yang tidak pernah dipakai lagi. Sebelumnya cuma ada di
 * Admin\ProductController sebagai method privat, begitu foto profil anggota
 * ditambahkan, logika itu bakal disalin ke controller kedua.
 */
class ImageStorageService
{
    /**
     * Simpan gambar yang diunggah, kembalikan path relatifnya
     * (mis. "member-photos/abc.jpg") buat disimpan ke kolom database.
     * Null kalau tidak ada berkas yang diunggah.
     *
     * @param  string  $folder  nama folder di dalam disk 'public'
     */
    public function store(?UploadedFile $image, string $folder): ?string
    {
        if (! $image) {
            return null;
        }

        return $image->store($folder, 'public');
    }

    /**
     * Hapus berkas gambar kalau memang ada. Aman dipanggil dengan null atau
     * dengan path yang berkasnya sudah tidak ada, tidak akan error.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Ganti gambar lama dengan yang baru: simpan yang baru, hapus yang lama,
     * lalu kembalikan path baru. Kalau tidak ada berkas baru diunggah, path
     * lama dipertahankan apa adanya (tidak ikut terhapus).
     */
    public function replace(?UploadedFile $baru, ?string $pathLama, string $folder): ?string
    {
        if (! $baru) {
            return $pathLama;
        }

        $this->delete($pathLama);

        return $this->store($baru, $folder);
    }
}
