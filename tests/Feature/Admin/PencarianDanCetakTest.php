<?php

use App\Models\Member;
use App\Models\OrderPeriod;
use App\Models\Product;
use App\Models\User;

/**
 * Pencarian di daftar pengurus, dan rekap yang bisa dicetak.
 *
 * Dua hal yang dipakai sehari-hari tapi sempat tidak ada:
 * anggotanya nanti sekitar 100 orang terbagi lima halaman, dan daftar belanja
 * grosirnya dibawa ke toko dalam bentuk kertas.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();

    foreach ([
        ['0001 A', 'Siti Nurhaliza'],
        ['0002 A', 'Budi Santoso'],
        ['0003 A', 'Dewi Lestari'],
    ] as [$kode, $nama]) {
        Member::create([
            'member_code' => $kode, 'full_name' => $nama,
            'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
        ]);
    }

    foreach ([
        ['Sembako', 'Beras Pandan Wangi'],
        ['Sembako', 'Gula Pasir'],
        ['Minuman', 'Kopi Bubuk'],
    ] as [$kategori, $nama]) {
        Product::create([
            'category' => $kategori, 'name' => $nama, 'buy_price' => 10000,
            'sell_price' => 12000, 'is_fluctuating' => false,
            'has_stock_tracking' => false, 'is_active' => true,
        ]);
    }
});

test('anggota bisa dicari lewat namanya', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.members.index', ['cari' => 'siti']))
        ->assertOk()
        ->assertSee('Siti Nurhaliza')
        ->assertDontSee('Budi Santoso');
});

test('anggota bisa dicari lewat kode anggotanya', function () {
    // Pengurus sering pegang kodenya saja, mis. waktu mencocokkan rekap.
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.members.index', ['cari' => '0002']))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Nurhaliza');
});

test('produk bisa dicari lewat nama maupun kategorinya', function () {
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.products.index', ['cari' => 'beras']))
        ->assertOk()
        ->assertSee('Beras Pandan Wangi')
        ->assertDontSee('Kopi Bubuk');

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.products.index', ['cari' => 'minuman']))
        ->assertOk()
        ->assertSee('Kopi Bubuk')
        ->assertDontSee('Beras Pandan Wangi');
});

test('pencarian tanpa hasil TIDAK bilang daftarnya masih kosong', function () {
    // Dulu keduanya memakai pesan yang sama, jadi salah ketik satu huruf
    // membuat daftar anggota seolah-olah belum diisi sama sekali.
    $this->actingAs($this->admin, 'web')
        ->get(route('admin.members.index', ['cari' => 'zzzz']))
        ->assertOk()
        ->assertSee('Anggota tidak ditemukan')
        ->assertDontSee('Belum ada data anggota');
});

test('daftar tanpa kata kunci tetap memakai pesan "belum ada isinya"', function () {
    Member::query()->delete();

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.members.index'))
        ->assertOk()
        ->assertSee('Belum ada data anggota');
});

test('kata kunci ikut terbawa waktu pindah halaman', function () {
    // Tanpa withQueryString(), menekan halaman 2 akan menghapus pencariannya
    // dan pengurus balik lagi ke seluruh daftar.
    //
    // Butuh lebih dari 20 anggota supaya paginasinya benar-benar muncul.
    foreach (range(10, 40) as $i) {
        Member::create([
            'member_code' => sprintf('%04d A', $i), 'full_name' => "Anggota Uji {$i}",
            'whatsapp_number' => '628111111111', 'password' => 'anggota123', 'is_active' => true,
        ]);
    }

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.members.index', ['cari' => 'Uji']))
        ->assertOk()
        ->assertSee('cari=Uji', false);
});

test('rekap belanja punya tombol cetak dan kop khusus kertas', function () {
    $periode = OrderPeriod::create([
        'label' => 'Pemesanan September 2026', 'start_date' => now()->subDay(),
        'end_date' => now()->addDay(), 'status' => 'open',
    ]);

    $html = $this->actingAs($this->admin, 'web')
        ->get(route('admin.order-periods.rekap', $periode))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('window.print()');
    expect($html)->toContain('print-only');
    expect($html)->toContain('Koperasi Mitra Dharma Praja');
});

test('tanggal ditulis dalam bahasa Indonesia', function () {
    // Locale aplikasi 'id' — kalau tertinggal 'en', tanggal di struk dan rekap
    // tertulis "04 July" alih-alih "04 Juli".
    $periode = OrderPeriod::create([
        'label' => 'Uji Bulan', 'start_date' => '2026-07-04',
        'end_date' => '2026-07-19', 'status' => 'open',
    ]);

    $this->actingAs($this->admin, 'web')
        ->get(route('admin.order-periods.rekap', $periode))
        ->assertOk()
        ->assertSee('Juli 2026')
        ->assertDontSee('July 2026');
});
