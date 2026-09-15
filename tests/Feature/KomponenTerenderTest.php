<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;
use App\Services\KontakPengurusService;
use Database\Seeders\DatabaseSeeder;

/*
| Semua halaman dirender, lalu dicek tidak ada tag komponen Blade (<x-...>)
| yang tertinggal mentah di HTML.
|
| KENAPA ADA: kolom "Kode Akses" di form OPD sempat ditulis dengan direktif
| @if di dalam atribut tag <x-text-input>. Blade tidak memproses tag seperti
| itu dan membiarkannya mentah; browser tidak menganggapnya kotak isian.
| Akibatnya sejak awal admin tidak bisa menambah OPD dari aplikasi, dan tidak
| ada tes yang tahu karena tes lama mengirim data langsung ke server tanpa
| merender formnya. Ketahuan baru 15 Sep 2026 lewat uji di browser.
*/

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // Nomor WhatsApp koperasi diisi, supaya tombol "Hubungi Pengurus" ikut
    // dirender dan ikut diperiksa di semua halaman.
    app(KontakPengurusService::class)->simpanNomorWhatsApp('081234567890');
});

function pastikanTerender($respons, string $url): void
{
    $respons->assertOk();
    expect(preg_match('/<x-[a-z]/', $respons->getContent()))
        ->toBe(0, "Ada tag komponen mentah di $url");
}

test('semua halaman admin terender tanpa tag komponen mentah', function () {
    $this->actingAs(User::first());

    $periode = OrderPeriod::yangSedangDibuka();
    $pesanan = Order::whereNotNull('total_amount')->first();

    foreach ([
        route('admin.dashboard'),
        route('admin.products.index'), route('admin.products.create'), route('admin.products.edit', Product::first()),
        route('admin.members.index'), route('admin.members.create'), route('admin.members.edit', Member::first()),
        route('admin.opd-departments.index'), route('admin.opd-departments.create'), route('admin.opd-departments.edit', OpdDepartment::first()),
        route('admin.order-periods.index'), route('admin.order-periods.create'), route('admin.order-periods.edit', $periode), route('admin.order-periods.rekap', $periode),
        route('admin.orders.index'), route('admin.orders.show', $pesanan), route('admin.orders.struk', $pesanan),
        route('admin.product-requests.index'), route('admin.profile.edit'), route('admin.settings.edit'),
    ] as $url) {
        pastikanTerender($this->get($url), $url);
    }
});

test('semua halaman anggota terender tanpa tag komponen mentah', function () {
    $anggota = Member::whereHas('orders', fn ($q) => $q->whereNotNull('total_amount'))->first();
    $pesanan = $anggota->orders()->whereNotNull('total_amount')->first();
    $this->actingAs($anggota, 'member');

    foreach ([
        route('member.dashboard'), route('member.orders.create'), route('member.orders.index'),
        route('member.orders.show', $pesanan), route('member.orders.struk', $pesanan),
        route('member.profile.edit'), route('member.product-requests.index'), route('member.product-requests.create'),
        route('catalog.index'), route('catalog.show', Product::first()),
    ] as $url) {
        pastikanTerender($this->get($url), $url);
    }
});

test('halaman tamu dan non-anggota terender tanpa tag komponen mentah', function () {
    foreach ([route('catalog.index'), route('masuk'), route('login'), route('member.login'), route('non-member.login')] as $url) {
        pastikanTerender($this->get($url), $url);
    }

    $opd = OpdDepartment::first();
    $this->withSession(['non_member_opd_id' => $opd->id]);

    foreach ([route('non-member.orders.create'), route('non-member.product-requests.create')] as $url) {
        pastikanTerender($this->get($url), $url);
    }
});
