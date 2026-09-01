<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;

beforeEach(function () {
    $this->member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan Agustus 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->beras = Product::create([
        'category' => 'Sembako',
        'name' => 'Beras 5kg',
        'buy_price' => 65000,
        'sell_price' => 70000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);

    $this->telur = Product::create([
        'category' => 'Sayur & Segar',
        'name' => 'Telur Ayam 1kg',
        'buy_price' => 28000,
        'sell_price' => null,
        'is_fluctuating' => true,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);
});

test('anggota bisa lihat form pesan saat periode dibuka', function () {
    $this->actingAs($this->member, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        ->assertSee('Beras 5kg');
});

test('form pesan nampilin pesan kosong kalau belum ada periode dibuka', function () {
    $this->periode->update(['status' => OrderPeriodStatus::Closed]);

    $this->actingAs($this->member, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        ->assertSee('Belum ada periode pemesanan yang dibuka');
});

test('pesanan produk non-fluktuatif langsung terverifikasi & totalnya benar', function () {
    $response = $this->actingAs($this->member, 'member')
        ->post(route('member.orders.store'), [
            'delivery_method' => 'ambil',
            'quantity' => [$this->beras->id => 3],
        ]);

    $order = Order::first();
    $response->assertRedirect(route('member.orders.show', $order));

    expect($order->status)->toBe(OrderStatus::Verified);
    expect((float) $order->total_amount)->toBe(210000.0); // 3 x 70.000
    expect($order->orderItems)->toHaveCount(1);
    expect((float) $order->orderItems->first()->price_at_order)->toBe(70000.0);
});

test('pesanan produk fluktuatif tetap pending & harga belum terkunci', function () {
    $this->actingAs($this->member, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->telur->id => 2],
    ]);

    $order = Order::first();

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_amount)->toBeNull();
    expect($order->orderItems->first()->price_at_order)->toBeNull();
});

test('pesanan campuran (non-fluktuatif + fluktuatif) tetap pending sampai semua item berharga', function () {
    $this->actingAs($this->member, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [
            $this->beras->id => 1,
            $this->telur->id => 1,
        ],
    ]);

    $order = Order::first();

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->orderItems)->toHaveCount(2);
});

test('minimal 1 produk harus diisi jumlahnya sebelum kirim pesanan', function () {
    $this->actingAs($this->member, 'member')
        ->post(route('member.orders.store'), [
            'delivery_method' => 'ambil',
            'quantity' => [$this->beras->id => 0],
        ])
        ->assertSessionHasErrors('quantity');

    $this->assertDatabaseCount('orders', 0);
});

test('anggota BOLEH kirim lebih dari 1 pesanan di periode yang sama', function () {
    // Dulu ini dilarang — ternyata aturan itu tidak pernah diminta, hasil
    // salah baca transkrip. Yang dibahas di rapat adalah kasus suami-istri
    // (dua akun anggota berbeda) yang sama-sama memesan, dan kesimpulannya
    // justru "Kalau beda mah enggak apa-apa" (CLAUDE.md, Lampiran A).
    // Wajar juga secara praktik: orang bisa teringat barang yang kelupaan
    // sebelum periodenya ditutup.
    $this->actingAs($this->member, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 1],
    ]);

    $response = $this->actingAs($this->member, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 2],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseCount('orders', 2);
});

test('form pesan tetap terbuka walau anggota sudah pernah memesan periode ini', function () {
    $this->actingAs($this->member, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 1],
    ]);

    $this->actingAs($this->member, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        // Formnya masih ada, bukan pesan "kamu sudah mengirim pesanan"
        ->assertSee('Beras 5kg')
        ->assertDontSee('Kamu sudah mengirim pesanan');
});

test('anggota tidak bisa lihat pesanan milik anggota lain', function () {
    $anggotaLain = Member::create([
        'member_code' => '0020 A',
        'full_name' => 'Anggota Lain',
        'whatsapp_number' => '628987654321',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->actingAs($anggotaLain, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 1],
    ]);
    $pesananOrangLain = Order::first();

    $this->actingAs($this->member, 'member')
        ->get(route('member.orders.show', $pesananOrangLain))
        ->assertForbidden();
});

test('tamu yang belum login ditolak dari halaman pesan', function () {
    $this->get(route('member.orders.create'))
        ->assertRedirect(route('member.login'));
});
