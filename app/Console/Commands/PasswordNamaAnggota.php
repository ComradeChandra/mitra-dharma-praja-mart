<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\User;
use App\Services\PasswordNamaService;
use Illuminate\Console\Command;

/**
 * php artisan koperasi:password-nama-anggota [--simpan]
 *
 * Tanpa --simpan: hanya menampilkan password baru tiap anggota.
 * Dengan --simpan: password SEMUA anggota diganti jadi "nama depan + 12345678".
 *
 * Logikanya di PasswordNamaService; perintah ini cuma menampilkan dan
 * memanggil service.
 */
class PasswordNamaAnggota extends Command
{
    protected $signature = 'koperasi:password-nama-anggota
        {--simpan : Benar-benar mengganti password (tanpa ini cuma pratinjau)}';

    protected $description = 'Ganti password semua anggota jadi nama depan + 12345678';

    public function handle(PasswordNamaService $passwordNama): int
    {
        // Pratinjau selalu ditampilkan, supaya hasilnya bisa dicek dulu
        $this->table(['Kode', 'Nama', 'Password baru'], array_map('array_values', $passwordNama->pratinjau()));

        if (! $this->option('simpan')) {
            $this->comment('Ini baru pratinjau, belum ada yang diganti. Jalankan ulang dengan --simpan untuk mengganti.');

            return self::SUCCESS;
        }

        // Antrean lupa password yang ikut ditutup dicatat atas nama Admin Utama
        $adminUtama = User::where('role', AdminRole::AdminUtama)->where('is_active', true)->orderBy('id')->first();
        if (! $adminUtama) {
            $this->error('Tidak ada akun Admin Utama yang aktif.');

            return self::FAILURE;
        }

        $jumlah = $passwordNama->terapkan($adminUtama);
        $this->info("Password {$jumlah} anggota sudah diganti.");

        return self::SUCCESS;
    }
}
