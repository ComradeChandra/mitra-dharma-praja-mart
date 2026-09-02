<?php

namespace App\View\Components\Order;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Satu kelompok kategori di form pemesanan: judul kategori plus baris-baris
 * produk di bawahnya.
 *
 * Sengaja komponen berbasis CLASS. Penyaring di halaman itu perlu tahu nama
 * dan id tiap produk dalam bentuk yang bisa dibaca JavaScript, supaya judul
 * kategorinya ikut hilang kalau semua produk di bawahnya tersaring habis.
 * Merakit daftar itu adalah pekerjaan menyiapkan data, dan menurut aturan di
 * CLAUDE.md tempatnya bukan di dalam file Blade.
 */
class CategoryGroup extends Component
{
    /** @var Collection<int, array{id: int, nama: string}> */
    public Collection $ringkasan;

    /**
     * @param  string  $category  nama kategorinya, mis. "Sembako"
     * @param  Collection  $products  produk aktif di kategori ini
     */
    public function __construct(
        public string $category,
        public Collection $products,
    ) {
        // Nama dikecilkan di sini, bukan di JavaScript, supaya pencocokannya
        // tidak perlu mengecilkan ulang tiap kali orang mengetik satu huruf.
        $this->ringkasan = $products->map(fn ($produk) => [
            'id' => $produk->id,
            'nama' => mb_strtolower($produk->name),
        ])->values();
    }

    public function render(): View
    {
        return view('components.order.category-group');
    }
}
