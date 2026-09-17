<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel order_items.
 *
 * Satu baris = satu produk di dalam satu pesanan, beserta jumlahnya.
 * price_at_order mengunci harga produk pada saat pesanan (atau saat verifikasi
 * untuk produk fluktuatif) supaya riwayat pesanan lama tidak berubah kalau
 * harga produk di katalog berubah di kemudian hari.
 */
#[Fillable(['order_id', 'product_id', 'quantity', 'stok_saat_pesan', 'price_at_order'])]
class OrderItem extends Model
{
    /**
     * Cast price_at_order jadi desimal supaya perhitungan total tidak meleset,
     * dan stok_saat_pesan jadi integer (bisa null untuk produk tanpa pelacakan).
     */
    protected function casts(): array
    {
        return [
            'price_at_order' => 'decimal:2',
            'stok_saat_pesan' => 'integer',
        ];
    }

    /**
     * Barang ini dipesan melebihi stok yang tercatat saat itu.
     *
     * Cuma berarti untuk produk yang stoknya dilacak: stok_saat_pesan diisi
     * snapshot stok waktu dipesan (lihat OrderService::createOrderItemsFor).
     * Produk pre-order murni stok_saat_pesan-nya null, jadi tidak pernah
     * dianggap melebihi stok.
     */
    public function melebihiStok(): bool
    {
        return $this->stok_saat_pesan !== null && $this->quantity > $this->stok_saat_pesan;
    }

    /**
     * Berapa jumlah yang melebihi stok tercatat (0 kalau tidak melebihi).
     */
    public function kelebihan(): int
    {
        return $this->melebihiStok() ? $this->quantity - $this->stok_saat_pesan : 0;
    }

    /**
     * Relasi: item ini bagian dari pesanan mana.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relasi: item ini merujuk ke produk yang mana.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
