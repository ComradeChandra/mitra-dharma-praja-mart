<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Product;

/**
 * Tautan navigasi di header.
 *
 * Pernah salah: halaman katalog dan detail produk memakai layout publik,
 * sementara tautan navigasinya cuma ditulis di layout anggota. Jadi begitu
 * anggota menekan "Katalog", seluruh nav mendatarnya hilang dan yang tersisa
 * tinggal logo dengan menu profil.
 *
 * Yang dijaga di sini: navnya sama di semua halaman untuk peran yang sama.
 */
beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $this->produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras Pandan Wangi', 'buy_price' => 65000,
        'sell_price' => 72000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);
});

$tautanAnggota = ['Beranda', 'Katalog', 'Pesan Produk', 'Pesanan Saya', 'Permintaan Produk'];

test('anggota melihat nav yang sama di halamannya sendiri maupun di katalog', function () use ($tautanAnggota) {
    foreach (['member.dashboard', 'catalog.index', 'member.orders.create'] as $rute) {
        $respons = $this->actingAs($this->anggota, 'member')->get(route($rute))->assertOk();

        foreach ($tautanAnggota as $label) {
            $respons->assertSee($label);
        }
    }
});

test('nav anggota juga muncul di halaman detail produk', function () use ($tautanAnggota) {
    $respons = $this->actingAs($this->anggota, 'member')
        ->get(route('catalog.show', $this->produk))
        ->assertOk();

    foreach ($tautanAnggota as $label) {
        $respons->assertSee($label);
    }
});

test('non-anggota punya jalan ke katalog dari halaman pesannya', function () {
    // Dulu tidak ada: tautannya cuma "Pesan" dan "Usulkan Produk", jadi dari
    // halaman itu tidak ada jalan balik ke katalog selain menekan tombol
    // kembali di browser.
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.orders.create'))
        ->assertOk()
        ->assertSee('Katalog')
        ->assertSee('Usulkan Produk');
});

test('tamu tidak diberi tautan navigasi, cuma tombol masuk', function () {
    $respons = $this->get(route('catalog.index'))->assertOk();

    $respons->assertSee('Masuk')
        ->assertDontSee('Pesanan Saya')
        ->assertDontSee('Permintaan Produk');
});

test('tautan halaman yang sedang dibuka ditandai buat pembaca layar', function () {
    // aria-current="page" bikin pembaca layar menyebut "halaman saat ini",
    // tidak cuma mengandalkan beda warna.
    $this->actingAs($this->anggota, 'member')
        ->get(route('catalog.index'))
        ->assertSee('aria-current="page"', false);
});
