<?php

namespace App\Models;

use App\Enums\CancelledBy;
use App\Enums\DeliveryMethod;
use App\Enums\KeputusanStok;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Model untuk tabel orders.
 *
 * Satu baris = satu pesanan dari anggota ATAU non-anggota (dibedakan lewat
 * kolom user_type). Alur status: pending → verified → invoiced, dan bisa
 * berhenti di cancelled dari mana saja. Pembayaran QRIS dicatat terpisah di
 * kolom payment_status.
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
    'payment_status',
    'payment_proof_path',
    'paid_declared_at',
    'payment_confirmed_at',
    'cancelled_at',
    'cancelled_by',
    'cancellation_reason',
    'catatan_pengurus',
    'keputusan_stok',
    'keputusan_stok_oleh',
    'keputusan_stok_pada',
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
        'payment_status' => PaymentStatus::Unpaid->value,
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
            'payment_status' => PaymentStatus::class,
            'paid_declared_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancelled_by' => CancelledBy::class,
            'delivery_method' => DeliveryMethod::class,
            'total_amount' => 'decimal:2',
            'keputusan_stok' => KeputusanStok::class,
            'keputusan_stok_pada' => 'datetime',
        ];
    }

    /**
     * Nomor struk yang enak dibaca dan disebut lewat telepon, mis.
     * "MDP-2609-0154": kode toko, tahun-bulan pesanan dibuat, lalu id-nya.
     *
     * Id aslinya tetap dipakai di URL dan di database. Ini murni tampilan,
     * supaya pemesan punya patokan waktu menanyakan pesanannya ke pengurus.
     */
    public function nomorStruk(): string
    {
        return sprintf('MDP-%s-%04d', $this->created_at->format('ym'), $this->id);
    }

    /**
     * Saring per status pesanan. Nilai yang tidak dikenal diabaikan, jadi
     * pemanggilnya tidak perlu menulis if sendiri (pola yang sama dengan
     * Product::scopeCari).
     */
    public function scopeStatusPesanan(Builder $query, ?string $status): Builder
    {
        return $status && OrderStatus::tryFrom($status)
            ? $query->where('status', $status)
            : $query;
    }

    /**
     * Pesanan yang masih berlaku, alias belum dibatalkan.
     *
     * Dipakai di tempat yang menghitung SEMUA status termasuk pending (rekap
     * sudah/belum belanja, pemberitahuan "kamu sudah mengirim pesanan",
     * pembayaran yang menunggu dicocokkan). Rekap uang tidak butuh ini karena
     * sudah cuma mengambil pesanan berstatus verified/invoiced.
     */
    public function scopeBelumDibatalkan(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatus::Cancelled);
    }

    /**
     * Pesanan ini sudah dibatalkan pemesan atau pengurus.
     */
    public function dibatalkan(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /**
     * Saring per status pembayaran. Terpisah dari status pesanan karena
     * keduanya bergerak sendiri-sendiri: pesanan bisa sudah terverifikasi tapi
     * belum dibayar.
     */
    public function scopeStatusPembayaran(Builder $query, ?string $status): Builder
    {
        return $status && PaymentStatus::tryFrom($status)
            ? $query->where('payment_status', $status)
            : $query;
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

    /**
     * Relasi: pengurus yang memutuskan tinjauan stok (null selama belum
     * diputuskan). Foreign key-nya keputusan_stok_oleh, bukan tebakan otomatis.
     */
    public function peninjauStok(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keputusan_stok_oleh');
    }

    /**
     * Pesanan yang menunggu ditinjau pengurus karena melebihi stok tercatat.
     * Dipakai badge daftar pesanan dan pengingat dasbor. Cukup baca kolom
     * keputusan_stok, tidak perlu memuat item-nya.
     */
    public function scopePerluTinjauanStok(Builder $query): Builder
    {
        return $query->where('keputusan_stok', KeputusanStok::Menunggu);
    }

    /**
     * Pesanan ini melebihi stok dan pengurus belum memutuskan. Sekadar
     * pembacaan kolom, tidak menyentuh item. Namanya beda dari scope
     * perluTinjauanStok() supaya keduanya tidak bertabrakan (scope dipanggil
     * gaya query, ini dipanggil pada instance).
     */
    public function menungguTinjauanStok(): bool
    {
        return $this->keputusan_stok === KeputusanStok::Menunggu;
    }

    /**
     * Ada barang yang dipesan melebihi stok tercatat. Butuh orderItems dimuat.
     */
    public function adaMelebihiStok(): bool
    {
        return $this->orderItems->contains(fn (OrderItem $item) => $item->melebihiStok());
    }

    /**
     * Barang-barang yang jumlahnya melebihi stok tercatat. Butuh orderItems
     * dimuat; dipakai panel "Tinjauan stok" di halaman detail pesanan.
     *
     * @return Collection<int, OrderItem>
     */
    public function barangMelebihiStok(): Collection
    {
        return $this->orderItems->filter(fn (OrderItem $item) => $item->melebihiStok())->values();
    }
}
