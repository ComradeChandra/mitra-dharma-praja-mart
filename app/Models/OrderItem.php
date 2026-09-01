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
#[Fillable(['order_id', 'product_id', 'quantity', 'price_at_order'])]
class OrderItem extends Model
{
    /**
     * Cast price_at_order jadi desimal supaya perhitungan total tidak meleset.
     */
    protected function casts(): array
    {
        return [
            'price_at_order' => 'decimal:2',
        ];
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
