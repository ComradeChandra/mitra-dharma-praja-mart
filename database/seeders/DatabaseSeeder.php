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
        $this->call([
            // Urutan penting: OPD & Admin duluan (data dasar), lalu anggota &
            // produk contoh, baru pesanan contoh (yang butuh keduanya sudah ada).
            OpdDepartmentSeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
