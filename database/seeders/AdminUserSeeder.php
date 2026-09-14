<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

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
        $email = config('koperasi.admin.email');
        $password = (string) config('koperasi.admin.password');

        if (app()->isProduction()) {
            // Password bawaan tertulis di repo ini; di server itu sama dengan
            // tanpa password.
            if (strlen($password) < 8 || $password === 'admin12345') {
                throw new RuntimeException('Isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env (password minimal 8 karakter, bukan admin12345) sebelum menjalankan seeder di server.');
            }

            // Di server akun yang sudah ada tidak disentuh, supaya menjalankan
            // seeder lagi tidak menimpa password yang sudah diganti pengurus.
            User::firstOrCreate(['email' => $email], [
                'name' => 'Pengurus Koperasi',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            return;
        }

        // Laptop pengembang: updateOrCreate, jadi menjalankan seeder lagi
        // sekaligus mengembalikan password demo kalau sempat diganti.
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Pengurus Koperasi',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
    }
}
