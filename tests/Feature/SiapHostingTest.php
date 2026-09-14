<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\User;
use App\Services\OrderLinkService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
| Perilaku yang baru terasa begitu aplikasi dipasang di server sungguhan
| (15 Sep 2026, persiapan hosting untuk presentasi final).
*/

function pura2DiServer(): void
{
    app()->detectEnvironment(fn () => 'production');
}

/** Jalankan seeder langsung: lewat artisan, mode produksi meminta konfirmasi dulu. */
function jalankanSeeder(string $kelas): void
{
    app($kelas)->setContainer(app())->run();
}

test('di server, db:seed tidak memasukkan data contoh maupun OPD berkode sama', function () {
    pura2DiServer();
    config(['koperasi.admin.email' => 'pengurus@koperasi.id', 'koperasi.admin.password' => 'KataSandiKuat2026']);

    jalankanSeeder(DatabaseSeeder::class);

    expect(User::count())->toBe(1);
    expect(User::first()->email)->toBe('pengurus@koperasi.id');
    expect(Hash::check('KataSandiKuat2026', User::first()->password))->toBeTrue();
    expect(Member::count())->toBe(0);
    expect(OpdDepartment::count())->toBe(0);
    expect(Order::count())->toBe(0);
});

test('di server, seeder menolak password admin bawaan', function () {
    pura2DiServer();
    config(['koperasi.admin.password' => 'admin12345']);

    expect(fn () => jalankanSeeder(AdminUserSeeder::class))->toThrow(RuntimeException::class);
    expect(User::count())->toBe(0);
});

test('di server, menjalankan seeder lagi tidak menimpa password yang sudah diganti pengurus', function () {
    pura2DiServer();
    config(['koperasi.admin.email' => 'pengurus@koperasi.id', 'koperasi.admin.password' => 'KataSandiKuat2026']);
    jalankanSeeder(AdminUserSeeder::class);
    User::first()->update(['password' => Hash::make('SudahDigantiPengurus')]);

    jalankanSeeder(AdminUserSeeder::class);

    expect(Hash::check('SudahDigantiPengurus', User::first()->password))->toBeTrue();
});

test('tautan bayar non-anggota tetap sah walau diakses lewat https dan domain lain', function () {
    // Banyak hosting memasang HTTPS lewat proxy, dan domain bisa pindah. Tanda
    // tangannya sekarang tidak menyertakan domain maupun http/https.
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $periode = OrderPeriod::create(['label' => 'Pemesanan Agustus 2026', 'start_date' => now()->subDay(), 'end_date' => now()->addWeek(), 'status' => OrderPeriodStatus::Open]);
    $pesanan = Order::create(['order_period_id' => $periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Teti', 'opd_id' => $opd->id, 'whatsapp_number' => '62811', 'status' => OrderStatus::Verified, 'total_amount' => 70000]);

    $tautan = app(OrderLinkService::class)->untukPemesan($pesanan);
    $diServer = preg_replace('#^https?://[^/]+#', 'https://koperasi-mitradharma.id', $tautan);

    $this->withSession(['non_member_opd_id' => $opd->id])->get($diServer)->assertOk();
});

test('di server, error tak terduga tampil sebagai halaman Indonesia tanpa membocorkan kode', function () {
    config(['app.debug' => false]);
    Route::get('/_uji-error-server', fn () => throw new RuntimeException('rahasia: DB_PASSWORD=xyz di OrderService.php baris 42'));

    $html = $this->get('/_uji-error-server')->assertStatus(500)->getContent();

    expect($html)
        ->toContain('Terjadi kesalahan di server')
        ->toContain('lang="id"')
        ->not->toContain('rahasia')
        ->not->toContain('OrderService.php');
});
