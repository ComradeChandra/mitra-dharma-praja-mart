<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun admin (pengurus koperasi).
 *
 * Admin login pakai email + password standar (lihat CLAUDE.md, Aktor).
 * Karena tidak ada halaman registrasi (admin bukan self-register), akun
 * pertama ini dibuat lewat seeder supaya bisa langsung dipakai login/testing.
 *
 * Kredensial untuk testing, ganti passwordnya sebelum dipakai sungguhan:
 * Email    : admin@mitradharma.test
 * Password : admin12345
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate: kalau email ini sudah ada, datanya di-update (bukan
        // dibuat dobel), aman dijalankan berkali-kali tanpa bikin akun duplikat
        User::updateOrCreate(
            ['email' => 'admin@mitradharma.test'],
            [
                'name' => 'Pengurus Koperasi',
                'password' => Hash::make('admin12345'),
                'email_verified_at' => now(),
            ]
        );
    }
}
