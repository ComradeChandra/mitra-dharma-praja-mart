<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/**
 * Struk pemesanan resmi (halaman /struk).
 *
 * Halaman tersendiri yang siap dicetak atau disimpan jadi PDF lewat dialog
 * cetak browser. Dipakai bertiga: anggota, non-anggota, dan pengurus.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
    ]);
    $this->anggotaLain = Member::create([
        'member_code' => '0002 A', 'full_name' => 'Budi Santoso',
        'whatsapp_number' => '628122222222', 'password' => 'anggota123', 'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $this->periode = OrderPeriod::create([
        'label' => 'Pemesanan September 2026', 'start_date' => now()->subDay(),
        'end_date' => now()->addDay(), 'status' => 'open',
    ]);

    $this->beras = Product::create([
        'category' => 'Sembako', 'name' => 'Beras Pandan Wangi', 'buy_price' => 65000,
        'sell_price' => 72000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);

    $this->pesanan = Order::create([
        'order_period_id' => $this->periode->id, 'user_type' => UserType::Member,
        'member_id' => $this->anggota->id, 'whatsapp_number' => '628111111111',
        'status' => OrderStatus::Verified, 'total_amount' => 144000,
        'delivery_method' => 'antar', 'delivery_address' => 'Jl. Kebon Kopi No. 12',
    ]);
    $this->pesanan->orderItems()->create([
        'product_id' => $this->beras->id, 'quantity' => 2, 'price_at_order' => 72000,
    ]);
});

test('struk memuat identitas koperasi dan rincian pesanannya', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.struk', $this->pesanan))
        ->assertOk()
        ->assertSee('Koperasi Mitra Dharma Praja')
        ->assertSee('Kebersamaan untuk Kesejahteraan')
        ->assertSee($this->pesanan->nomorStruk())
        ->assertSee('Siti Nurhaliza')
        ->assertSee('0001 A')
        ->assertSee('Beras Pandan Wangi')
        ->assertSee('Rp144.000')
        ->assertSee('Jl. Kebon Kopi No. 12');
});

test('struk menegaskan ini bukti pemesanan, bukan bukti pembayaran', function () {
    // Penting supaya tidak ada yang mengira pesanannya sudah lunas — ini
    // sistem pre-order, tagihannya menyusul lewat WhatsApp.
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.struk', $this->pesanan))
        ->assertSee('bukan bukti pembayaran');
});

test('anggota tidak bisa membuka struk milik anggota lain', function () {
    $punyaOrangLain = Order::create([
        'order_period_id' => $this->periode->id, 'user_type' => UserType::Member,
        'member_id' => $this->anggotaLain->id, 'whatsapp_number' => '628122222222',
        'status' => OrderStatus::Verified, 'total_amount' => 72000,
    ]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.struk', $punyaOrangLain))
        ->assertForbidden();
});

test('tamu tidak bisa membuka struk siapa pun', function () {
    $this->get(route('member.orders.struk', $this->pesanan))
        ->assertRedirect(route('member.login'));
});

test('pengurus bisa membuka struk pesanan mana pun', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.orders.struk', $this->pesanan))
        ->assertOk()
        ->assertSee($this->pesanan->nomorStruk());
});

test('non-anggota cuma bisa membuka struk pesanan OPD-nya sendiri', function () {
    $opdLain = OpdDepartment::create(['name' => 'Dinas Kesehatan', 'access_code' => 'opd54321']);

    $punyaOpdLain = Order::create([
        'order_period_id' => $this->periode->id, 'user_type' => UserType::NonMember,
        'non_member_name' => 'Rina', 'opd_id' => $opdLain->id,
        'whatsapp_number' => '628133333333', 'status' => OrderStatus::Verified, 'total_amount' => 72000,
    ]);

    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.orders.struk', $punyaOpdLain))
        ->assertForbidden();
});

test('struk punya tombol cetak dan bagian yang tidak ikut tercetak', function () {
    // Tombol dan navigasi diberi class no-print supaya hasil cetaknya cuma
    // struknya saja (aturannya di resources/css/app.css).
    $html = $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.struk', $this->pesanan))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('window.print()');
    expect($html)->toContain('no-print');
    expect($html)->toContain('class="struk');
});

test('pesanan yang totalnya belum final tidak diberi tautan WhatsApp', function () {
    // Produk fluktuatif belum berharga sampai pengurus memverifikasi, jadi
    // strukmya belum layak dibagikan sebagai angka final.
    $this->pesanan->update(['status' => OrderStatus::Pending, 'total_amount' => null]);

    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.struk', $this->pesanan))
        ->assertOk()
        ->assertDontSee('wa.me', false);
});

test('halaman pesanan mengantar ke struknya', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.orders.show', $this->pesanan))
        ->assertOk()
        ->assertSee(route('member.orders.struk', $this->pesanan), false);
});
