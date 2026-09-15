<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel settings: pengaturan koperasi yang diubah pengurus
 * lewat Admin -> Pengaturan. Isinya satu baris saja.
 *
 * Membaca & menyimpannya lewat KontakPengurusService, jangan langsung dari
 * controller atau Blade.
 */
#[Fillable(['whatsapp_number'])]
class Setting extends Model {}
