<?php

namespace App\View\Components\Admin;

use App\Services\KategoriProdukService;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Pilihan kategori di form produk: dropdown berisi kategori yang sudah ada,
 * plus pilihan "+ Buat kategori baru" yang memunculkan kotak isian nama
 * kategori baru. Dipakai di admin/products/_form.blade.php.
 *
 * Komponen kelas (bukan anonim) supaya logika "pilihan mana yang terpilih
 * saat form dibuka" tidak menumpuk di Blade.
 *
 * Contoh:
 * <x-admin.pilih-kategori :daftar="$daftarKategori" :nilai="old('category', $product->category ?? '')" :nilai-baru="old('category_baru')" />
 */
class PilihKategori extends Component
{
    /** Nilai dropdown saat form dibuka: nama kategori, PILIHAN_BARU, atau '' (belum memilih). */
    public string $pilihan;

    /** Isi kotak "nama kategori baru" saat form dibuka. */
    public string $baru;

    /** Penanda pilihan "+ Buat kategori baru", dioper ke view & Alpine. */
    public string $pilihanBaru = KategoriProdukService::PILIHAN_BARU;

    public function __construct(
        public Collection $daftar,
        ?string $nilai = '',
        ?string $nilaiBaru = '',
    ) {
        $nilai = trim((string) $nilai);
        $this->baru = (string) $nilaiBaru;

        if ($nilai === $this->pilihanBaru) {
            // Form dikirim ulang setelah memilih "+ Buat kategori baru".
            $this->pilihan = $this->pilihanBaru;
        } elseif ($nilai !== '' && ! $daftar->contains($nilai)) {
            // Kategori yang belum ada di daftar (mis. kategori baru yang tadi
            // diketik, tapi form-nya ditolak validasi karena kolom lain):
            // tampilkan lagi sebagai kategori baru, isinya tidak hilang.
            $this->pilihan = $this->pilihanBaru;
            $this->baru = $nilai;
        } elseif ($daftar->isEmpty()) {
            // Website baru, belum ada kategori sama sekali: langsung minta
            // nama kategori baru, dropdown-nya tidak ada isinya.
            $this->pilihan = $this->pilihanBaru;
        } else {
            $this->pilihan = $nilai;
        }
    }

    public function render(): View
    {
        return view('components.admin.pilih-kategori');
    }
}
