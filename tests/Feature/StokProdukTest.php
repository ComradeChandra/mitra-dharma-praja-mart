<?php

use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\OrderPeriod;
use App\Models\Product;

/**
 * Aturan stok — dua hal yang beda tapi nyambung:
 *
 * 1. Pelanggan CUMA boleh lihat "Tersedia"/"Tidak tersedia", TIDAK PERNAH
 *    angka stoknya (aturan tegas di CLAUDE.md, dasarnya kekhawatiran Teh Teti
 *    di rapat: angka stok bikin bingung karena ini sistem pre-order).
 * 2. Stok berkurang otomatis pas ada yang pesan, tapi cuma buat produk yang
 *    memang dilacak stoknya (has_stock_tracking).
 */
beforeEach(function () {
    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan Agustus 2026',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628111111111',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    // Produk dengan pelacakan stok, stok 50.
    $this->berstok = Product::create([
        'category' => 'Sembako',
        'name' => 'Beras Berstok',
        'buy_price' => 65000,
        'sell_price' => 70000,
        'is_fluctuating' => false,
        'has_stock_tracking' => true,
        'stock' => 50,
        'is_active' => true,
    ]);

    // Produk pre-order murni — stoknya tidak dilacak sama sekali.
    $this->preOrder = Product::create([
        'category' => 'Sembako',
        'name' => 'Minyak Pre Order',
        'buy_price' => 30000,
        'sell_price' => 35000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);
});

test('katalog publik TIDAK menampilkan angka stok, cuma status tersedia', function () {
    $response = $this->get(route('catalog.index'));

    $response->assertSee('Beras Berstok');
    $response->assertSee('Tersedia');

    // Angka stoknya tidak boleh bocor. Dicek lewat label yang dulu dipakai
    // buat nampilinnya — bukan sekadar cari angka "50" di HTML, soalnya angka
    // itu juga muncul di nama class Tailwind (bg-gray-50, text-gray-500, dst)
    // jadi bakal false positive.
    $response->assertDontSee('Stok tersisa');
    $response->assertDontSee('Stok tersedia:');
});

test('produk berstok habis tampil "Tidak tersedia" di katalog', function () {
    $this->berstok->update(['stock' => 0]);

    $this->get(route('catalog.index'))->assertSee('Tidak tersedia');
});

test('produk tanpa pelacakan stok selalu dianggap tersedia', function () {
    expect($this->preOrder->isAvailable())->toBeTrue();

    // Bahkan kalau kolom stock-nya kebetulan 0/null — karena memang tidak dipakai.
    $this->preOrder->update(['stock' => 0]);
    expect($this->preOrder->fresh()->isAvailable())->toBeTrue();
});

test('form pesan anggota juga tidak membocorkan angka stok', function () {
    $response = $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'));

    $response->assertSee('Beras Berstok');
    $response->assertDontSee('Stok tersedia:');
});

test('stok berkurang otomatis setelah anggota memesan', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->berstok->id => 3],
    ]);

    // 50 - 3 = 47
    expect($this->berstok->fresh()->stock)->toBe(47);
});

test('produk tanpa pelacakan stok TIDAK ikut dikurangi', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => 'ambil',
        'quantity' => [$this->preOrder->id => 5],
    ]);

    // Tetap null/kosong — produk pre-order murni memang tidak punya angka stok.
    expect($this->preOrder->fresh()->stock)->toBeNull();
});

test('tidak bisa pesan melebihi stok yang tersedia', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), [
            'delivery_method' => 'ambil',
            'quantity' => [$this->berstok->id => 51],
        ])
        ->assertSessionHasErrors('quantity.'.$this->berstok->id);

    // Pesanannya tidak jadi dibuat, stok tidak berubah.
    $this->assertDatabaseCount('orders', 0);
    expect($this->berstok->fresh()->stock)->toBe(50);
});
