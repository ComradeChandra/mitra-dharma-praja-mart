<?php

namespace App\Models;

use App\Enums\OrderPeriodStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel order_periods.
 *
 * Jendela waktu pemesanan yang dibuka/ditutup admin. Selama status "open" dan
 * tanggal sekarang ada di antara start_date & end_date, anggota/non-anggota
 * bisa mengirim pesanan.
 */
#[Fillable(['label', 'start_date', 'end_date', 'status'])]
class OrderPeriod extends Model
{
    /**
     * Cast tanggal jadi objek Carbon, dan status jadi native PHP Enum
     * (OrderPeriodStatus) supaya nilainya selalu salah satu dari open/closed.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => OrderPeriodStatus::class,
        ];
    }

    /**
     * Periode yang benar-benar sedang menerima pesanan: statusnya open DAN
     * hari ini masih di rentang tanggalnya.
     *
     * Syarat tanggal ini penting. Kalau cuma mengandalkan status, admin yang
     * lupa menutup periode bikin pemesanan jalan terus lewat tanggal selesai.
     */
    public function scopeSedangDibuka(Builder $query): Builder
    {
        return $query->where('status', OrderPeriodStatus::Open)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }

    /**
     * Periode yang sedang dibuka, atau null kalau tidak ada.
     *
     * Dipusatkan di sini karena dipakai di katalog, dua form pemesanan, dan
     * dashboard admin. Kalau aturannya disalin ke tiap controller, perubahan
     * kecil gampang ada yang kelewat.
     */
    public static function yangSedangDibuka(): ?self
    {
        return static::sedangDibuka()->latest('start_date')->first();
    }

    /**
     * Relasi: satu periode bisa punya banyak pesanan yang masuk selama periode itu berjalan.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
