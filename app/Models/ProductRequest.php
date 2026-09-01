<?php

namespace App\Models;

use App\Enums\ProductRequestStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel product_requests.
 *
 * Menyimpan usulan produk baru dari anggota/non-anggota yang belum ada di
 * katalog. Admin meninjau lalu approve/reject (lihat CLAUDE.md, Modul 8).
 */
#[Fillable(['user_type', 'member_id', 'requester_label', 'product_name', 'status'])]
class ProductRequest extends Model
{
    /**
     * Cast user_type & status jadi native PHP Enum.
     */
    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
            'status' => ProductRequestStatus::class,
        ];
    }

    /**
     * Relasi: peminta anggota (null kalau yang mengajukan non-anggota).
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
