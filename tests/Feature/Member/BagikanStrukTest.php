<?php

use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;

/**
 * Anggota membagikan struk pesanannya sendiri lewat WhatsApp.
 *
 * Beda dari invoice sisi admin: tautannya TANPA nomor tujuan, jadi WhatsApp
 * membuka daftar kontak dan anggota bebas mengirimnya ke siapa pun. Kalau
 * dipatok ke nomornya sendiri, dia cuma bisa kirim ke diri sendiri.
 */
beforeEach(function () {
    OrderPeriod::create([
        'label' => 'Periode Uji',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->beras = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg',
        'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->telur = Product::create([
        'category' => 'Sayur & Segar', 'name' => 'Telur Ayam 1kg',
        'buy_price' => 28000, 'sell_price' => null,
        'is_fluctuating' => true, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);
});

test('pesanan yang sudah final punya tombol bagikan struk', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 2],
    ]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', Order::first()))
        ->assertOk()
        ->assertSee('Bagikan Struk lewat WhatsApp')
        // Tautan wa.me TANPA nomor tujuan
        ->assertSee('https://wa.me/?text=', false);
});

test('pesanan yang masih menunggu verifikasi TIDAK bisa dibagikan', function () {
    // Produk fluktuatif → harga belum dikunci → total belum final.
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->telur->id => 2],
    ]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', Order::first()))
        ->assertOk()
        ->assertDontSee('Bagikan Struk lewat WhatsApp');
});

test('teks struk memuat nama, produk, total, & cara terima barang', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'antar',
        'delivery_address' => 'Jl. Kebon Kopi No. 12',
        'quantity' => [$this->beras->id => 2],
    ]);

    $teks = app(\App\Services\WhatsAppInvoiceService::class)
        ->generateInvoiceText(Order::first());

    expect($teks)->toContain('Siti Nurhaliza');
    expect($teks)->toContain('Beras 5kg');
    expect($teks)->toContain('140.000');
    expect($teks)->toContain('Diantar');
    expect($teks)->toContain('Jl. Kebon Kopi No. 12');
});

test('halaman struk anggota menampilkan cara terima barang', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'antar',
        'delivery_address' => 'Jl. Kebon Kopi No. 12',
        'quantity' => [$this->beras->id => 1],
    ]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', Order::first()))
        ->assertSee('Cara terima barang')
        ->assertSee('Diantar')
        ->assertSee('Jl. Kebon Kopi No. 12');
});

test('anggota tidak bisa membagikan struk pesanan orang lain', function () {
    $anggotaLain = Member::create([
        'member_code' => '0002 A',
        'full_name' => 'Budi Santoso',
        'whatsapp_number' => '628222222222',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->beras->id => 1],
    ]);

    $this->actingAs($anggotaLain, 'member')
        ->get(route('member.orders.show', Order::first()))
        ->assertForbidden();
});
