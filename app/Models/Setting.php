<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel settings: pengaturan koperasi yang diubah pengurus
 * lewat Admin -> Pengaturan. Isinya satu baris saja.
 *
 * Membaca & menyimpannya lewat service, jangan langsung dari controller atau
 * Blade: nomor WhatsApp lewat KontakPengurusService, persentase SHU lewat
 * ShuService.
 */
#[Fillable(['whatsapp_number', 'shu_persen_min', 'shu_persen_maks'])]
class Setting extends Model {}
