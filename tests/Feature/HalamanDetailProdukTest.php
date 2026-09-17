<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Product;

/**
 * Halaman detail produk (/katalog/{id}).
 *
 * Dua hal yang paling dijaga di sini: produk nonaktif tidak boleh bisa dibuka
 * dengan menebak angka di URL, dan angka stok tidak boleh sampai terlihat
 * pelanggan (aturan stok di CLAUDE.md).
 */
beforeEach(function () {
    $this->beras = Product::create([
        'category' => 'Sembako',
        'name' => 'Beras Pandan Wangi',
        'buy_price' => 65000,
        'sell_price' => 72000,
        'is_fluctuating' => false,
        'has_stock_tracking' => true,
        'stock' => 42,
        'is_active' => true,
    ]);
});

test('halaman detail menampilkan keterangan produknya', function () {
    $this->get(route('catalog.show', $this->beras))
        ->assertOk()
        ->assertSee('Beras Pandan Wangi')
        ->assertSee('Sembako')
        ->assertSee('Rp72.000')
        ->assertSee('Tersedia');
});

test('produk nonaktif tidak bisa dibuka lewat URL', function () {
    // Bukan sekadar disembunyikan dari daftar: dibuka langsung pun harus ditolak.
    $this->beras->update(['is_active' => false]);

    $this->get(route('catalog.show', $this->beras))->assertNotFound();
});

test('angka stok TIDAK muncul di halaman detail', function () {
    $html = $this->get(route('catalog.show', $this->beras))->getContent();

    // Dicek dari teks yang benar-benar terbaca orang, bukan dari HTML mentah.
    // Angka 42 bisa saja kebetulan muncul di dalam nama kelas atau path SVG.
    $terlihat = strip_tags($html);

    expect($terlihat)->not->toContain('42');
    expect($terlihat)->not->toContain('Stok');
});

test('produk yang stoknya 0 tetap ditandai tersedia (pre-order)', function () {
    // Angka stok tidak lagi menentukan ketersediaan: selama produknya aktif,
    // tetap bisa dipesan (keputusan 16 Sep 2026).
    $this->beras->update(['stock' => 0]);

    $this->get(route('catalog.show', $this->beras))
        ->assertSee('Tersedia')
        ->assertDontSee('Tidak tersedia');
});

test('produk fluktuatif menampilkan penjelasan, bukan angka harga', function () {
    $telur = Product::create([
        'category' => 'Sayur & Segar',
        'name' => 'Telur Ayam',
        'buy_price' => 28000,
        'sell_price' => null,
        'is_fluctuating' => true,
        'has_stock_tracking' => false,
        'is_active' => true,
    ]);

    $this->get(route('catalog.show', $telur))
        ->assertOk()
        ->assertSee('Harga fluktuatif')
        ->assertSee('diisi pengurus');
});

test('kartu di katalog mengantar ke halaman detail', function () {
    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertSee(route('catalog.show', $this->beras), false);
});

test('tombol pesan menyesuaikan siapa yang sedang masuk', function () {
    // Tamu diarahkan ke halaman masuk terpadu, bukan langsung ke form pesan.
    $this->get(route('catalog.show', $this->beras))
        ->assertSee(route('masuk'), false)
        ->assertSee('Masuk buat Pesan');

    $anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $this->actingAs($anggota, 'member')
        ->get(route('catalog.show', $this->beras))
        ->assertSee(route('member.orders.create'), false)
        ->assertSee('Pesan Sekarang');
});

test('non-anggota diarahkan ke form pesan non-anggota', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $this->withSession(['non_member_opd_id' => $opd->id])
        ->get(route('catalog.show', $this->beras))
        ->assertSee(route('non-member.orders.create'), false);
});

test('bagian "lainnya" cuma memuat produk sekategori yang masih aktif', function () {
    $sekategori = Product::create([
        'category' => 'Sembako', 'name' => 'Gula Pasir', 'buy_price' => 15000,
        'sell_price' => 17000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);
    $bedaKategori = Product::create([
        'category' => 'Minuman', 'name' => 'Kopi Bubuk', 'buy_price' => 18000,
        'sell_price' => 21000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => true,
    ]);
    $nonaktif = Product::create([
        'category' => 'Sembako', 'name' => 'Beras Merah Lama', 'buy_price' => 30000,
        'sell_price' => 34000, 'is_fluctuating' => false,
        'has_stock_tracking' => false, 'is_active' => false,
    ]);

    $this->get(route('catalog.show', $this->beras))
        ->assertSee($sekategori->name)
        ->assertDontSee($bedaKategori->name)
        ->assertDontSee($nonaktif->name);
});
