<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel orders.
 *
 * Satu baris = satu pesanan dari anggota ATAU non-anggota (dibedakan lewat
 * kolom user_type). Alur status: pending → verified → invoiced. tidak ada
 * status "dibayar/lunas" karena aplikasi ini bukan e-commerce.
 */
#[Fillable([
    'order_period_id',
    'user_type',
    'member_id',
    'non_member_name',
    'opd_id',
    'whatsapp_number',
    'delivery_method',
    'delivery_address',
    'status',
    'total_amount',
])]
class Order extends Model
{
    /**
     * Nilai bawaan di tingkat model, bukan cuma di database.
     *
     * Default kolom di migration baru berlaku setelah barisnya dibaca ulang.
     * Objek hasil Order::create() yang belum di-refresh atributnya masih null,
     * dan itu bikin delivery_method->label() error saat merender invoice.
     *
     * "Ambil" dipilih karena paling aman, tidak mengarang alamat pengantaran.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'delivery_method' => DeliveryMethod::Ambil->value,
    ];

    /**
     * Cast user_type, status & delivery_method jadi native PHP Enum, dan
     * total_amount jadi desimal.
     */
    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
            'status' => OrderStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Relasi: pesanan ini termasuk periode pemesanan yang mana.
     */
    public function orderPeriod(): BelongsTo
    {
        return $this->belongsTo(OrderPeriod::class);
    }

    /**
     * Relasi: pemesan anggota (null kalau pemesannya non-anggota).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * OPD yang dipilih non-anggota. Null kalau pemesannya anggota.
     */
    public function opdDepartment(): BelongsTo
    {
        // Foreign key harus ditulis eksplisit. Kolomnya bernama opd_id, bukan
        // opd_department_id seperti tebakan otomatis dari nama method ini.
        return $this->belongsTo(OpdDepartment::class, 'opd_id');
    }

    /**
     * Relasi: daftar produk + jumlah yang dipesan dalam pesanan ini.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
