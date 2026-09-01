<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderPeriodStatus;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;

/**
 * Cara terima barang: diantar atau ambil sendiri di koperasi.
 *
 * Aturan yang dijaga di sini:
 * - Alamat anggota terisi OTOMATIS di form, tapi tetap boleh diganti kalau
 *   kali ini mau dikirim ke tempat lain.
 * - Kalau "ambil sendiri", tidak ada alamat yang tersimpan sama sekali.
 * - Alamat disalin ke pesanan, jadi anggota mengubah alamat profilnya nanti
 *   TIDAK ikut mengubah riwayat pesanan lama.
 */
beforeEach(function () {
    $this->periode = OrderPeriod::create([
        'label' => 'Periode Uji',
        'start_date' => now()->subDay(),
        'end_date' => now()->addWeek(),
        'status' => OrderPeriodStatus::Open,
    ]);

    $this->produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras 5kg',
        'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'address' => 'Jl. Kebon Kopi No. 12, Cimahi Tengah',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
});

test('alamat anggota terisi otomatis di form pesan', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        ->assertSee('Jl. Kebon Kopi No. 12, Cimahi Tengah')
        ->assertSee('Terisi otomatis dari data kamu');
});

test('anggota tanpa alamat tersimpan tetap bisa buka form (kolomnya kosong)', function () {
    $this->anggota->update(['address' => null]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.create'))
        ->assertOk()
        ->assertDontSee('Terisi otomatis dari data kamu');
});

test('pesanan diantar menyimpan alamat pengantarannya', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Antar->value,
        'delivery_address' => 'Jl. Kebon Kopi No. 12, Cimahi Tengah',
        'quantity' => [$this->produk->id => 2],
    ]);

    $order = Order::first();

    expect($order->delivery_method)->toBe(DeliveryMethod::Antar);
    expect($order->delivery_address)->toBe('Jl. Kebon Kopi No. 12, Cimahi Tengah');
});

test('anggota boleh mengirim ke alamat LAIN, bukan alamat tersimpannya', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Antar->value,
        'delivery_address' => 'Kantor DPMTSP, Jl. Demang Hardjakusumah',
        'quantity' => [$this->produk->id => 1],
    ]);

    $order = Order::first();

    expect($order->delivery_address)->toBe('Kantor DPMTSP, Jl. Demang Hardjakusumah');
    // Alamat di profilnya TIDAK ikut berubah cuma karena sekali kirim ke tempat lain.
    expect($this->anggota->fresh()->address)->toBe('Jl. Kebon Kopi No. 12, Cimahi Tengah');
});

test('pesanan ambil sendiri TIDAK menyimpan alamat apa pun', function () {
    // Alamat sengaja ikut dikirim — harus diabaikan karena metodenya "ambil".
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Ambil->value,
        'delivery_address' => 'Alamat nyasar yang tidak boleh tersimpan',
        'quantity' => [$this->produk->id => 1],
    ]);

    $order = Order::first();

    expect($order->delivery_method)->toBe(DeliveryMethod::Ambil);
    expect($order->delivery_address)->toBeNull();
});

test('kalau memilih diantar tapi alamat kosong, pesanan ditolak', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), [
            'delivery_method' => DeliveryMethod::Antar->value,
            'delivery_address' => '',
            'quantity' => [$this->produk->id => 1],
        ])
        ->assertSessionHasErrors('delivery_address');

    $this->assertDatabaseCount('orders', 0);
});

test('cara terima barang wajib dipilih', function () {
    $this->actingAs($this->anggota, 'member')
        ->post(route('member.orders.store'), [
            'quantity' => [$this->produk->id => 1],
        ])
        ->assertSessionHasErrors('delivery_method');

    $this->assertDatabaseCount('orders', 0);
});

test('mengubah alamat profil TIDAK mengubah alamat pesanan lama', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Antar->value,
        'delivery_address' => 'Alamat waktu memesan',
        'quantity' => [$this->produk->id => 1],
    ]);

    // Anggota pindah rumah, alamat profilnya diperbarui.
    $this->anggota->update(['address' => 'Alamat baru setelah pindah']);

    expect(Order::first()->delivery_address)->toBe('Alamat waktu memesan');
});

test('non-anggota juga bisa memilih diantar atau ambil sendiri', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->post(route('non-member.orders.store'), [
            'non_member_name' => 'Teh Teti',
            'whatsapp_number' => '628199988877',
            'delivery_method' => DeliveryMethod::Antar->value,
            'delivery_address' => 'Kantor Dinas Pendidikan',
            'quantity' => [$this->produk->id => 1],
        ]);

    $order = Order::first();

    expect($order->delivery_method)->toBe(DeliveryMethod::Antar);
    expect($order->delivery_address)->toBe('Kantor Dinas Pendidikan');
});

test('cara terima barang & alamat muncul di teks invoice WhatsApp', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Antar->value,
        'delivery_address' => 'Jl. Kebon Kopi No. 12',
        'quantity' => [$this->produk->id => 1],
    ]);

    $teks = app(\App\Services\WhatsAppInvoiceService::class)
        ->generateInvoiceText(Order::first()->load('orderItems.product', 'member', 'orderPeriod'));

    expect($teks)->toContain('Diantar');
    expect($teks)->toContain('Jl. Kebon Kopi No. 12');
});

test('invoice pesanan ambil sendiri tidak mencantumkan baris alamat', function () {
    $this->actingAs($this->anggota, 'member')->post(route('member.orders.store'), [
        'delivery_method' => DeliveryMethod::Ambil->value,
        'quantity' => [$this->produk->id => 1],
    ]);

    $teks = app(\App\Services\WhatsAppInvoiceService::class)
        ->generateInvoiceText(Order::first()->load('orderItems.product', 'member', 'orderPeriod'));

    expect($teks)->toContain('Ambil di koperasi');
    expect($teks)->not->toContain('Alamat:');
});
