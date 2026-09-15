<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\KontakPengurusService;
use App\Services\OrderCancellationService;
use App\Services\OrderService;

/*
|--------------------------------------------------------------------------
| Halaman Bantuan (FAQ) & Profil Saya anggota (16 Sep 2026)
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza', 'whatsapp_number' => '081234560001',
        'password' => 'anggota123', 'is_active' => true,
    ]);
    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan September 2026', 'start_date' => now()->subDay(), 'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);
    $this->pesan = fn (array $produk) => app(OrderService::class)->createOrder(
        $this->anggota, $this->periode,
        [['product_id' => Product::create($produk)->id, 'quantity' => 1]],
        DeliveryMethod::Ambil, null,
    );
});

test('halaman bantuan bisa dibuka siapa saja', function () {
    $this->get(route('bantuan'))
        ->assertOk()
        ->assertSee('Bantuan &amp; Pertanyaan Umum', false)
        ->assertSee('Saya anggota dan lupa password. Bagaimana?')
        ->assertSee('Siapa saja yang memakai aplikasi ini?');

    $this->actingAs($this->anggota, 'member')->get(route('bantuan'))->assertOk();

    $opd = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'rahasia123']);
    $this->withSession(['non_member_opd_id' => $opd->id])->get(route('bantuan'))->assertOk();
});

test('halaman bantuan menawarkan WhatsApp pengurus kalau nomornya diisi', function () {
    $this->get(route('bantuan'))->assertDontSee('Tanya pengurus lewat WhatsApp');

    app(KontakPengurusService::class)->simpanNomorWhatsApp('081234567890');

    $this->get(route('bantuan'))->assertSee('Tanya pengurus lewat WhatsApp');
});

test('kaki halaman dan menu anggota menautkan halaman bantuan', function () {
    $this->get(route('catalog.index'))->assertSee(route('bantuan'), false);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.dashboard'))
        ->assertSee('Bantuan &amp; FAQ', false);
});

test('profil anggota menampilkan kartu anggota dan ringkasan tahun ini', function () {
    // Harga pasti (masuk hitungan belanja), harga menyusul (cuma dihitung
    // sebagai pesanan), dan satu yang dibatalkan (tidak dihitung sama sekali)
    ($this->pesan)(['category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'stock' => 0, 'is_active' => true]);
    ($this->pesan)(['category' => 'Sayur', 'name' => 'Telur 1kg', 'buy_price' => 0, 'sell_price' => null,
        'is_fluctuating' => true, 'has_stock_tracking' => false, 'stock' => 0, 'is_active' => true]);
    $batal = ($this->pesan)(['category' => 'Sembako', 'name' => 'Gula 1kg', 'buy_price' => 15000, 'sell_price' => 17000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'stock' => 0, 'is_active' => true]);
    app(OrderCancellationService::class)->batalkanOlehPemesan($batal);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.profile.edit'))
        ->assertOk()
        ->assertSee('Anggota aktif · 0001 A')
        ->assertSee('Terdaftar sejak')
        ->assertSeeInOrder(['Pesanan tahun '.now()->year, '2'])
        ->assertSee('Rp70.000')
        ->assertSee(route('bantuan').'#pembayaran', false)
        ->assertSee('Butuh bantuan?');
});
