<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\OrderService;

/**
 * Aturan stok pada sistem pre-order (diperbarui 16 Sep 2026):
 *
 * 1. Pelanggan TIDAK PERNAH melihat angka stok, hanya "Tersedia" (aturan
 *    tegas di CLAUDE.md).
 * 2. Pesanan TIDAK PERNAH ditolak karena stok — ini pre-order, koperasi baru
 *    belanja setelah pesanan terkumpul. Stok tetap berkurang otomatis (buat
 *    produk yang dilacak) dan BOLEH jadi minus, sebagai penanda bagi pengurus
 *    bahwa perlu belanja lebih. Untuk menyembunyikan produk, pengurus
 *    menonaktifkannya (bukan lewat stok).
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

test('produk berstok yang stoknya 0 tetap bisa dipesan (pre-order)', function () {
    // Angka stok tidak lagi menentukan ketersediaan. Selama produknya aktif,
    // ia tetap tampil "Tersedia" dan bisa dipesan.
    $this->berstok->update(['stock' => 0]);

    $this->get(route('catalog.index'))
        ->assertSee('Beras Berstok')
        ->assertSee('Tersedia')
        ->assertDontSee('Tidak tersedia');
});

test('produk yang dinonaktifkan tidak muncul di katalog', function () {
    // Cara pengurus menyembunyikan produk sekarang lewat status aktif,
    // bukan lewat stok.
    $this->berstok->update(['is_active' => false]);

    expect($this->berstok->fresh()->isAvailable())->toBeFalse();
    $this->get(route('catalog.index'))->assertDontSee('Beras Berstok');
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

test('boleh pesan melebihi stok — tidak ditolak, dan stok jadi minus', function () {
    // Inti keputusan 16 Sep 2026: pesanan pre-order tidak ditolak karena stok.
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), [
            'delivery_method' => 'ambil',
            'quantity' => [$this->berstok->id => 51],
        ])
        ->assertSessionHasNoErrors();

    // Pesanannya jadi, dan stok jadi -1 sebagai penanda perlu belanja 1 lebih.
    $this->assertDatabaseCount('orders', 1);
    expect($this->berstok->fresh()->stock)->toBe(-1);
});

test('service tetap membuat pesanan walau stok kurang, stok jadi minus', function () {
    // Tanpa validasi form pun (mis. kondisi balapan dua pemesan), service
    // tidak menolak — stok cukup dibiarkan minus.
    $this->berstok->update(['stock' => 3]);

    app(OrderService::class)->createOrder(
        $this->anggota,
        $this->periode,
        [['product_id' => $this->berstok->id, 'quantity' => 5]],
        DeliveryMethod::Ambil,
        null,
    );

    $this->assertDatabaseCount('orders', 1);
    expect($this->berstok->fresh()->stock)->toBe(-2);
});

test('stok pas-pasan berkurang tepat sampai 0', function () {
    $this->berstok->update(['stock' => 4]);

    app(OrderService::class)->createOrder(
        $this->anggota,
        $this->periode,
        [['product_id' => $this->berstok->id, 'quantity' => 4]],
        DeliveryMethod::Ambil,
        null,
    );

    expect($this->berstok->fresh()->stock)->toBe(0);
});
