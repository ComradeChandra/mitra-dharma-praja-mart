<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/**
 * Judul di tab browser dan bahasa halaman.
 *
 * Pernah salah dua-duanya. Layout pengurus dan layout login memakai
 * `app()->getLocale()` yang isinya "en", padahal seluruh aplikasi berbahasa
 * Indonesia — pembaca layar jadi melafalkan teks Indonesia dengan bunyi
 * Inggris. Judulnya juga tidak pernah dioper, jadi enam halaman cuma tertulis
 * nama aplikasinya saja di tab.
 *
 * Penyebabnya halus: x-app-layout itu komponen berbasis class, dan layout
 * guest maupun non-anggota punya @props sendiri. Atribut yang tidak
 * dideklarasikan di sana tidak pernah jadi variabel, jadi `:title` dioper
 * tapi diam-diam terbuang.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->anggota = Member::create([
        'member_code' => '0001 A', 'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
    ]);
    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $this->produk = Product::create([
        'category' => 'Sembako', 'name' => 'Beras Pandan Wangi', 'buy_price' => 65000,
        'sell_price' => 72000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);
    $this->periode = OrderPeriod::create([
        'label' => 'Periode Uji', 'start_date' => now()->subDay(),
        'end_date' => now()->addDay(), 'status' => 'open',
    ]);
});

test('semua halaman menyatakan dirinya berbahasa Indonesia', function () {
    $halaman = [
        fn () => $this->get(route('catalog.index')),
        fn () => $this->get(route('masuk')),
        fn () => $this->get(route('member.login')),
        fn () => $this->actingAs($this->anggota, 'member')->get(route('member.dashboard')),
        fn () => $this->actingAs($this->admin, 'web')->get(route('admin.dashboard')),
        fn () => $this->actingAs($this->admin, 'web')->get(route('admin.products.create')),
    ];

    foreach ($halaman as $buka) {
        $buka()->assertOk()->assertSee('<html lang="id">', false);
    }
});

test('tiap halaman punya judul sendiri, bukan cuma nama aplikasi', function () {
    $harusnya = [
        ['Katalog Produk', fn () => $this->get(route('catalog.index'))],
        ['Masuk', fn () => $this->get(route('masuk'))],
        ['Beranda Anggota', fn () => $this->actingAs($this->anggota, 'member')->get(route('member.dashboard'))],
        ['Profil Saya', fn () => $this->actingAs($this->anggota, 'member')->get(route('member.profile.edit'))],
        ['Dashboard Admin', fn () => $this->actingAs($this->admin, 'web')->get(route('admin.dashboard'))],
        ['Data Anggota', fn () => $this->actingAs($this->admin, 'web')->get(route('admin.members.index'))],
        ['Rekap Periode', fn () => $this->actingAs($this->admin, 'web')->get(route('admin.order-periods.rekap', $this->periode))],
    ];

    foreach ($harusnya as [$judul, $buka]) {
        $buka()->assertOk()->assertSee('<title>'.$judul.' — '.config('app.name').'</title>', false);
    }
});

test('halaman non-anggota juga berjudul, bukan tertinggal', function () {
    // Layout non-anggota punya @props sendiri, jadi paling gampang terlewat.
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.orders.create'))
        ->assertOk()
        ->assertSee('<title>Pesan Produk — '.config('app.name').'</title>', false);

    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('non-member.product-requests.create'))
        ->assertOk()
        ->assertSee('<title>Usulkan Produk — '.config('app.name').'</title>', false);
});

test('halaman detail produk memakai nama produknya sebagai judul', function () {
    $this->get(route('catalog.show', $this->produk))
        ->assertOk()
        ->assertSee('<title>Beras Pandan Wangi — '.config('app.name').'</title>', false);
});
