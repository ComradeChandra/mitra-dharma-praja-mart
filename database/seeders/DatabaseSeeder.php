<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder utama, titik masuk saat menjalankan `php artisan db:seed` atau
 * `php artisan migrate:fresh --seed`. Tugasnya cuma memanggil seeder lain
 * secara berurutan (Single Responsibility: seeder ini tidak nulis data sendiri).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Di server sungguhan cuma akun pengurus yang dibuat. OPD, anggota,
        // dan produk asli diinput lewat halaman admin.
        $this->call(AdminUserSeeder::class);

        // Data contoh HANYA untuk laptop pengembang & tes. Kalau ikut ke
        // server, db:seed mengisi 24 anggota palsu, 48 pesanan palsu, dan 10 OPD
        // dengan kode akses yang sama ("opd12345") yang tertulis di repo ini.
        // Urutan penting: OPD dulu, lalu anggota & produk, baru pesanan.
        if (app()->environment('local', 'testing')) {
            $this->call([
                OpdDepartmentSeeder::class,
                DemoDataSeeder::class,
                DemoOrderSeeder::class,
            ]);
        }
    }
}
