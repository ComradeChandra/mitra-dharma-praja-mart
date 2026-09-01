<?php

use App\Models\Product;

/**
 * Pencarian produk di katalog publik.
 *
 * Sengaja form GET biasa (bukan pencarian langsung pakai JS) supaya hasilnya
 * bisa di-bookmark & dibagikan lewat tautan, dan tetap jalan di HP berkoneksi
 * lemot.
 */
beforeEach(function () {
    Product::create([
        'category' => 'Sembako', 'name' => 'Beras Pandan Wangi 5kg',
        'buy_price' => 65000, 'sell_price' => 70000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    Product::create([
        'category' => 'Kebersihan', 'name' => 'Sabun Cuci Piring 750ml',
        'buy_price' => 12000, 'sell_price' => 15000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    Product::create([
        'category' => 'Sembako', 'name' => 'Produk Nonaktif',
        'buy_price' => 1000, 'sell_price' => 2000,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => false,
    ]);
});

test('tanpa kata kunci, semua produk aktif tampil', function () {
    $this->get(route('catalog.index'))
        ->assertSee('Beras Pandan Wangi 5kg')
        ->assertSee('Sabun Cuci Piring 750ml')
        ->assertDontSee('Produk Nonaktif');
});

test('mencari sebagian nama produk tetap ketemu', function () {
    $this->get(route('catalog.index', ['cari' => 'beras']))
        ->assertSee('Beras Pandan Wangi 5kg')
        ->assertDontSee('Sabun Cuci Piring 750ml');
});

test('pencarian tidak peduli huruf besar/kecil', function () {
    $this->get(route('catalog.index', ['cari' => 'SABUN']))
        ->assertSee('Sabun Cuci Piring 750ml')
        ->assertDontSee('Beras Pandan Wangi 5kg');
});

test('bisa mencari lewat nama kategorinya juga', function () {
    $this->get(route('catalog.index', ['cari' => 'Kebersihan']))
        ->assertSee('Sabun Cuci Piring 750ml')
        ->assertDontSee('Beras Pandan Wangi 5kg');
});

test('produk nonaktif tidak pernah muncul walau dicari langsung', function () {
    $this->get(route('catalog.index', ['cari' => 'Nonaktif']))
        ->assertDontSee('Produk Nonaktif')
        ->assertSee('tidak ditemukan');
});

test('kata kunci yang tidak ketemu menampilkan pesan khusus, bukan "katalog kosong"', function () {
    // Bedanya penting: "tidak ditemukan" vs "belum ada produk sama sekali" —
    // jangan sampai orang mengira koperasinya belum punya barang apa pun.
    $this->get(route('catalog.index', ['cari' => 'barang-yang-tidak-ada']))
        ->assertSee('tidak ditemukan')
        ->assertDontSee('Belum ada produk yang tersedia di katalog saat ini');
});

test('kata kunci berisi spasi saja dianggap kosong (tampilkan semua)', function () {
    $this->get(route('catalog.index', ['cari' => '   ']))
        ->assertSee('Beras Pandan Wangi 5kg')
        ->assertSee('Sabun Cuci Piring 750ml');
});

test('kata kunci tetap terisi di kotak cari setelah hasil tampil', function () {
    $this->get(route('catalog.index', ['cari' => 'beras']))
        ->assertSee('value="beras"', false);
});
