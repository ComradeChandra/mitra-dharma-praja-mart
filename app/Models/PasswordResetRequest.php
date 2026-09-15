<?php

namespace App\Models;

use App\Enums\PasswordResetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan "lupa password" dari anggota, masuk ke antrean yang dilihat
 * semua pengurus. Aturannya (siapa boleh apa, kapan dianggap selesai) ada
 * di PasswordResetService, bukan di sini.
 */
#[Fillable(['member_id', 'note', 'status', 'handled_by', 'handled_at'])]
class PasswordResetRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PasswordResetStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** Pengurus yang menangani permintaan ini. */
    public function penangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** Permintaan yang belum ditangani siapa pun. */
    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', PasswordResetStatus::Menunggu);
    }
}
