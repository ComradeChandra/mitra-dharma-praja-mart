<?php

use App\Models\OrderPeriod;
use App\Models\Product;

test('halaman katalog bisa diakses tanpa login', function () {
    $this->get(route('catalog.index'))->assertOk();
});

test('katalog cuma menampilkan produk yang aktif', function () {
    Product::create([
        'category' => 'Sembako', 'name' => 'Produk Aktif',
        'buy_price' => 1000, 'sell_price' => 1500,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);
    Product::create([
        'category' => 'Sembako', 'name' => 'Produk Nonaktif',
        'buy_price' => 1000, 'sell_price' => 1500,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => false,
    ]);

    $response = $this->get(route('catalog.index'));

    $response->assertSee('Produk Aktif');
    $response->assertDontSee('Produk Nonaktif');
});

test('hero banner menampilkan info periode yang sedang dibuka', function () {
    // Tanggalnya sengaja relatif ke hari ini (bukan tanggal mati kayak
    // '2026-08-20'), soalnya periode sekarang beneran dicek rentang
    // tanggalnya — kalau ditulis mati, test-nya bakal busuk sendiri begitu
    // tanggal itu lewat.
    $selesai = now()->addWeek();

    OrderPeriod::create([
        'label' => 'Periode Agustus', 'start_date' => now()->subDay(),
        'end_date' => $selesai, 'status' => 'open',
    ]);

    // $escape di-set false karena teks yang dicari mengandung tanda kutip ganda
    // literal (bukan hasil {{ }} yang di-escape otomatis oleh Blade).
    // Pakai translatedFormat, sama seperti tampilannya: format() selalu
    // berbahasa Inggris, jadi tes ini sempat gagal tiap kali tanggal
    // selesainya jatuh di bulan Mei/Agustus/Oktober/Desember ("Oct" ≠ "Okt").
    $this->get(route('catalog.index'))
        ->assertSee('Periode "Periode Agustus" dibuka sampai '.$selesai->translatedFormat('d M Y'), false);
});

test('hero banner kasih tau kalau belum ada periode yang dibuka', function () {
    $this->get(route('catalog.index'))->assertSee('Belum ada periode pemesanan yang sedang dibuka saat ini');
});

test('periode yang status-nya open TAPI tanggalnya sudah lewat tidak dianggap dibuka', function () {
    // Ini penjaga buat bug yang pernah ada: dulu kodenya cuma cek kolom
    // status, jadi kalau admin lupa klik "tutup", pemesanan tetap jalan
    // lewat dari tanggal selesai, padahal pemesanan cuma boleh berjalan di
    // dalam rentang tanggal periodenya.
    OrderPeriod::create([
        'label' => 'Periode Kadaluarsa', 'start_date' => now()->subMonth(),
        'end_date' => now()->subDay(), 'status' => 'open',
    ]);

    $this->get(route('catalog.index'))
        ->assertSee('Belum ada periode pemesanan yang sedang dibuka saat ini')
        ->assertDontSee('Periode Kadaluarsa');
});

test('periode yang tanggalnya belum mulai juga tidak dianggap dibuka', function () {
    OrderPeriod::create([
        'label' => 'Periode Depan', 'start_date' => now()->addWeek(),
        'end_date' => now()->addWeeks(2), 'status' => 'open',
    ]);

    $this->get(route('catalog.index'))
        ->assertSee('Belum ada periode pemesanan yang sedang dibuka saat ini')
        ->assertDontSee('Periode Depan');
});
