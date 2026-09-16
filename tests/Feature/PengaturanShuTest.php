<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\ShuService;

/*
|--------------------------------------------------------------------------
| Mengatur persentase SHU dari halaman Pengaturan (16 Sep 2026)
|--------------------------------------------------------------------------
| Sebelumnya persentase SHU cuma bisa diubah lewat config/koperasi.php di
| server. Sekarang Admin Utama mengaturnya sendiri dari Admin -> Pengaturan,
| dan beranda anggota langsung mengikuti angka itu.
*/

beforeEach(function () {
    $this->utama = User::factory()->create();
    $this->staf = User::factory()->pengurus()->create();
    config(['koperasi.shu.persen_min' => 0.5, 'koperasi.shu.persen_maks' => 1.0]);
});

test('sebelum diatur dari aplikasi, dipakai angka bawaan config', function () {
    $persen = app(ShuService::class)->persentase();

    expect($persen['min'])->toBe(0.5);
    expect($persen['maks'])->toBe(1.0);
    expect(app(ShuService::class)->sudahDiaturDariAplikasi())->toBeFalse();
});

test('Admin Utama bisa mengatur persentase SHU dari Pengaturan', function () {
    $this->actingAs($this->utama, 'web')
        ->put(route('admin.settings.shu.update'), ['shu_persen_min' => '0.75', 'shu_persen_maks' => '1.5'])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    $persen = app(ShuService::class)->persentase();
    expect($persen['min'])->toBe(0.75);
    expect($persen['maks'])->toBe(1.5);
    expect(app(ShuService::class)->sudahDiaturDariAplikasi())->toBeTrue();
});

test('beranda anggota langsung mengikuti persentase yang diatur', function () {
    $anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti', 'whatsapp_number' => '628111111111',
        'password' => 'anggota123', 'is_active' => true,
    ]);

    // Persentase pasti 2% (kedua kotak sama) -> beranda menampilkan satu angka
    app(ShuService::class)->simpan(2.0, 2.0);
    // Belanja Rp1.000.000 -> SHU 2% = Rp20.000. Pakai stub RecapService lewat DB:
    $periode = OrderPeriod::create([
        'label' => 'P', 'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'status' => 'open',
    ]);
    $produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras', 'buy_price' => 1, 'sell_price' => 100000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);
    $order = Order::create([
        'order_period_id' => $periode->id, 'user_type' => UserType::Member,
        'member_id' => $anggota->id, 'whatsapp_number' => '628111111111',
        'status' => OrderStatus::Verified, 'total_amount' => 1000000,
    ]);
    $order->orderItems()->create(['product_id' => $produk->id, 'quantity' => 10, 'price_at_order' => 100000]);

    $this->actingAs($anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertOk()
        ->assertSee('Rp20.000')
        ->assertDontSee('persentase pasti ditentukan koperasi');
});

test('angka salah ditolak dan tidak tersimpan', function (array $isi) {
    $this->actingAs($this->utama, 'web')
        ->put(route('admin.settings.shu.update'), $isi)
        ->assertSessionHasErrors();

    expect(app(ShuService::class)->sudahDiaturDariAplikasi())->toBeFalse();
})->with([
    'terendah > tertinggi' => [['shu_persen_min' => '2', 'shu_persen_maks' => '1']],
    'kebesaran' => [['shu_persen_min' => '0.5', 'shu_persen_maks' => '150']],
    'kosong' => [['shu_persen_min' => '', 'shu_persen_maks' => '']],
    'bukan angka' => [['shu_persen_min' => 'satu', 'shu_persen_maks' => '2']],
]);

test('Pengurus biasa tidak bisa mengubah persentase SHU', function () {
    $this->actingAs($this->staf, 'web')
        ->put(route('admin.settings.shu.update'), ['shu_persen_min' => '5', 'shu_persen_maks' => '5'])
        ->assertForbidden();

    expect(app(ShuService::class)->sudahDiaturDariAplikasi())->toBeFalse();
});

test('halaman Pengaturan menampilkan kartu SHU dengan pratinjaunya', function () {
    app(ShuService::class)->simpan(0.75, 1.25);

    $this->actingAs($this->utama, 'web')
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Persentase SHU')
        ->assertSee('belanja Rp1.000.000')
        ->assertSee('value="0.75"', false)
        ->assertSee('value="1.25"', false);
});
