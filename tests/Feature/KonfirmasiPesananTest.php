<?php

use App\Enums\OrderPeriodStatus;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;

/*
| Kekhawatiran yang diangkat 14 Sep 2026: pemesan tidak pernah melihat lagi
| barang apa saja yang akan terkirim, dan barang dari pesanan sebelumnya bisa
| ikut terkirim lagi. Yang kedua terbukti di browser: setelah mengirim lalu
| menekan "kembali", browser mengisi ulang angka lama ke kotak jumlah
| (padahal ringkasannya bilang 0), dan pesanan berikutnya ikut memuatnya.
|
| Jendela konfirmasi & pengosongan form diuji di tests/js/order-form.test.mjs
| dan di browser. Yang diuji di sini bagian server-nya.
*/

beforeEach(function () {
    $this->periode = OrderPeriod::create(['label' => 'Pemesanan Agustus 2026', 'start_date' => now()->subDay(), 'end_date' => now()->addWeek(), 'status' => OrderPeriodStatus::Open]);
    $this->periodeLalu = OrderPeriod::create(['label' => 'Pemesanan Juli 2026', 'start_date' => now()->subMonth(), 'end_date' => now()->subMonth()->addWeek(), 'status' => OrderPeriodStatus::Closed]);
    $this->beras = Product::create(['category' => 'Sembako', 'name' => 'Beras 5kg', 'buy_price' => 65000, 'sell_price' => 70000, 'is_active' => true]);
    $this->anggota = Member::create(['member_code' => '0001 A', 'full_name' => 'Siti', 'whatsapp_number' => '628123', 'password' => 'rahasia123', 'is_active' => true]);
    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
});

function pesananAnggota(Member $anggota, OrderPeriod $periode, int $total): Order
{
    return Order::create(['order_period_id' => $periode->id, 'user_type' => UserType::Member, 'member_id' => $anggota->id, 'whatsapp_number' => '628123', 'status' => OrderStatus::Verified, 'total_amount' => $total]);
}

test('form pesan mematikan isi-ulang otomatis browser dan memuat jendela konfirmasi', function () {
    $html = $this->actingAs($this->anggota, 'member')->get(route('member.orders.create'))->getContent();

    expect($html)
        ->toMatch('/<form[^>]*autocomplete="off"[^>]*@submit="periksaDulu\(\$event\)"/')
        ->toContain('Periksa pesananmu')
        ->toContain('Ya, kirim pesanan');
});

test('anggota diberi tahu pesanan yang sudah terkirim di periode ini', function () {
    pesananAnggota($this->anggota, $this->periode, 140000);

    $this->actingAs($this->anggota, 'member')->get(route('member.orders.create'))
        ->assertSee('Kamu sudah mengirim 1 pesanan di periode ini')
        ->assertSee('Rp140.000');
});

test('pesanan periode lalu tidak ikut disebut', function () {
    pesananAnggota($this->anggota, $this->periodeLalu, 99000);

    $this->actingAs($this->anggota, 'member')->get(route('member.orders.create'))
        ->assertDontSee('Kamu sudah mengirim')
        ->assertDontSee('Rp99.000');
});

test('non-anggota cuma melihat pesanan dari sesinya sendiri, bukan pesanan rekan sekantor', function () {
    $punyaRekan = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Rekan', 'opd_id' => $this->opd->id, 'whatsapp_number' => '62811', 'status' => OrderStatus::Verified, 'total_amount' => 55000]);
    $punyaSendiri = Order::create(['order_period_id' => $this->periode->id, 'user_type' => UserType::NonMember, 'non_member_name' => 'Teti', 'opd_id' => $this->opd->id, 'whatsapp_number' => '62822', 'status' => OrderStatus::Verified, 'total_amount' => 77000]);

    $this->withSession(['non_member_opd_id' => $this->opd->id, 'non_member_order_ids' => [$punyaSendiri->id]])
        ->get(route('non-member.orders.create'))
        ->assertSee('Kamu sudah mengirim 1 pesanan di periode ini')
        ->assertSee('Rp77.000')
        ->assertDontSee('Rp55.000');
});
