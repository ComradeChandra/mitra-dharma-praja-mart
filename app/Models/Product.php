<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel products.
 *
 * Katalog produk pre-order koperasi. Stok bersifat opsional per produk
 * (has_stock_tracking), dan sell_price bisa kosong untuk produk fluktuatif
 * (is_fluctuating) sampai admin mengunci harganya saat verifikasi.
 */
#[Fillable([
    'category',
    'name',
    'buy_price',
    'sell_price',
    'image_path',
    'is_fluctuating',
    'has_stock_tracking',
    'stock',
    'is_active',
])]
class Product extends Model
{
    /**
     * Cast kolom-kolom boolean & angka desimal supaya tipe datanya benar
     * setiap kali diambil dari database (bukan string mentah).
     */
    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'is_fluctuating' => 'boolean',
            'has_stock_tracking' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Status ketersediaan yang boleh dilihat pelanggan.
     *
     * Angka stoknya sendiri cuma muncul di dashboard admin, sesuai aturan di
     * CLAUDE.md. Alasannya angka stok membingungkan di sistem pre-order,
     * karena barangnya sering belum ada saat dipesan.
     *
     * Produk yang stoknya tidak dilacak selalu dianggap tersedia. Itu justru
     * kasus pre-order murni: barangnya dibelanjakan setelah pesanan terkumpul.
     */
    public function isAvailable(): bool
    {
        if (! $this->has_stock_tracking) {
            return true;
        }

        return (int) $this->stock > 0;
    }

    /** Produk yang tampil di katalog pelanggan. */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Cari berdasarkan nama atau kategori.
     *
     * Kata kunci kosong diabaikan, jadi pemanggilnya tidak perlu menulis if
     * sendiri. Pencocokannya pakai LIKE supaya mengetik sebagian nama tetap
     * ketemu, dan tidak peduli huruf besar-kecil.
     */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        $kata = trim((string) $kata);

        if ($kata === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($kata) {
            $q->where('name', 'like', "%{$kata}%")
                ->orWhere('category', 'like', "%{$kata}%");
        });
    }

    /**
     * Relasi: satu produk bisa muncul di banyak item pesanan (dari pesanan berbeda-beda).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
