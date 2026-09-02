<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Services\RecapService;
use App\Services\WhatsAppInvoiceService;

/**
 * Satuan produk (mis. "renceng", "karung", "botol").
 *
 * Kolomnya opsional. Yang dijaga di sini ada dua sisi: kalau diisi, satuannya
 * ikut muncul di tempat orang membaca angka jumlah; kalau dikosongkan,
 * tampilannya harus persis seperti sebelum kolom ini ada.
 */
beforeEach(function () {
    $this->berasKarung = Product::create([
        'category' => 'Sembako',
        'name' => 'Beras Pandan Wangi',
        'unit' => 'karung',
        'buy_price' => 65000,
        'sell_price' => 72000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);

    // Sengaja tanpa satuan, buat menguji perilaku jatuh-kembalinya.
    $this->gulaPolos = Product::create([
        'category' => 'Sembako',
        'name' => 'Gula Pasir 1kg',
        'buy_price' => 15000,
        'sell_price' => 17000,
        'is_fluctuating' => false,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);
});

test('formatJumlah menempelkan satuan kalau kolomnya diisi', function () {
    expect($this->berasKarung->formatJumlah(3))->toBe('3 karung');
});

test('formatJumlah mengembalikan angka saja kalau satuan kosong', function () {
    expect($this->gulaPolos->formatJumlah(3))->toBe('3');
});

test('satuan muncul di katalog, menempel pada harga', function () {
    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertSee('/ karung');
});

test('satuan ikut tertulis di invoice WhatsApp', function () {
    $anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $periode = OrderPeriod::create([
        'label' => 'Periode Uji',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'status' => 'open',
    ]);

    $pesanan = Order::create([
        'order_period_id' => $periode->id,
        'user_type' => UserType::Member,
        'member_id' => $anggota->id,
        'whatsapp_number' => $anggota->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 216000,
    ]);

    $pesanan->orderItems()->create([
        'product_id' => $this->berasKarung->id,
        'quantity' => 3,
        'price_at_order' => 72000,
    ]);
    $pesanan->orderItems()->create([
        'product_id' => $this->gulaPolos->id,
        'quantity' => 2,
        'price_at_order' => 17000,
    ]);

    $teks = app(WhatsAppInvoiceService::class)->generateInvoiceText($pesanan);

    // Yang bersatuan ditulis lengkap, yang tidak tetap angka polos.
    expect($teks)->toContain('3 karung x Rp72.000');
    expect($teks)->toContain('2 x Rp17.000');
});

test('rekap belanja membawa jumlah yang sudah bersatuan', function () {
    $anggota = Member::create([
        'member_code' => '0002 A',
        'full_name' => 'Budi Santoso',
        'whatsapp_number' => '628122222222',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $periode = OrderPeriod::create([
        'label' => 'Periode Rekap',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'status' => 'open',
    ]);

    $pesanan = Order::create([
        'order_period_id' => $periode->id,
        'user_type' => UserType::Member,
        'member_id' => $anggota->id,
        'whatsapp_number' => $anggota->whatsapp_number,
        'status' => OrderStatus::Verified,
        'total_amount' => 360000,
    ]);
    $pesanan->orderItems()->create([
        'product_id' => $this->berasKarung->id,
        'quantity' => 5,
        'price_at_order' => 72000,
    ]);

    $baris = app(RecapService::class)->productsForPeriod($periode)->first();

    // Angka mentahnya tetap integer supaya masih bisa dipakai berhitung.
    expect($baris['jumlahDibutuhkan'])->toBe(5);
    expect($baris['jumlahDibutuhkanTertulis'])->toBe('5 karung');
});

test('admin bisa menyimpan produk lengkap dengan satuannya', function () {
    $this->actingAs(App\Models\User::factory()->create())
        ->post(route('admin.products.store'), [
            'category' => 'Minuman',
            'name' => 'Kopi Sachet',
            'unit' => 'renceng',
            'buy_price' => 18000,
            'sell_price' => 21000,
        ])->assertRedirect();

    expect(Product::where('name', 'Kopi Sachet')->value('unit'))->toBe('renceng');
});

test('satuan boleh dikosongkan saat menyimpan produk', function () {
    $this->actingAs(App\Models\User::factory()->create())
        ->post(route('admin.products.store'), [
            'category' => 'Minuman',
            'name' => 'Teh Kotak',
            'buy_price' => 4000,
            'sell_price' => 5000,
        ])->assertRedirect();

    expect(Product::where('name', 'Teh Kotak')->value('unit'))->toBeNull();
});
