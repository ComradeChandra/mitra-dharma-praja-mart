<?php

use App\Models\Product;
use App\Models\User;
use App\Services\KategoriProdukService;

/*
|--------------------------------------------------------------------------
| Kategori produk: dropdown kategori yang sudah ada + "Buat kategori baru",
| dan perapian isian (1 Okt 2026)
|--------------------------------------------------------------------------
| Katalog mengelompokkan produk berdasarkan tulisan kategori yang persis sama,
| jadi "Sembako", "sembako", dan "Sembako " tidak boleh tersimpan sebagai tiga
| kategori berbeda. Form produk punya dropdown kategori yang sudah ada + pilihan
| "+ Buat kategori baru", dan server merapikan isiannya sebelum disimpan.
*/

beforeEach(function () {
    $this->admin = User::factory()->create();

    // Pembuat produk singkat untuk tes ini.
    $this->buatProduk = fn (string $kategori, string $nama = 'Produk Uji') => Product::create([
        'category' => $kategori, 'name' => $nama, 'buy_price' => 1000, 'sell_price' => 1500,
        'is_fluctuating' => false, 'has_stock_tracking' => false, 'is_active' => true,
    ]);

    // Isian form tambah produk yang sah, tinggal ganti kategorinya.
    $this->isian = fn (string $kategori) => [
        'category' => $kategori, 'name' => 'Produk Baru', 'buy_price' => '1000', 'sell_price' => '1500', 'is_active' => '1',
    ];
});

test('form tambah dan ubah produk punya dropdown kategori + pilihan kategori baru', function () {
    ($this->buatProduk)('Sembako');
    $minuman = ($this->buatProduk)('Minuman');

    foreach ([route('admin.products.create'), route('admin.products.edit', $minuman)] as $alamat) {
        $this->actingAs($this->admin)->get($alamat)
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSee('>Minuman</option>', false)
            ->assertSee('>Sembako</option>', false)
            ->assertSee('+ Buat kategori baru')
            ->assertSee('name="category_baru"', false);
    }

    // Di form ubah, kategori produknya sendiri yang terpilih.
    $this->actingAs($this->admin)->get(route('admin.products.edit', $minuman))
        ->assertSee('<option value="Minuman" selected', false);
});

test('website baru tanpa kategori langsung meminta nama kategori baru', function () {
    $this->actingAs($this->admin)->get(route('admin.products.create'))
        ->assertOk()
        ->assertSee('<option value="'.KategoriProdukService::PILIHAN_BARU.'" selected', false);
});

test('memilih "+ Buat kategori baru" menyimpan nama yang diketik', function () {
    ($this->buatProduk)('Sembako');

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            ...($this->isian)(KategoriProdukService::PILIHAN_BARU),
            'category_baru' => '  Minuman   Dingin ',
        ])
        ->assertRedirect(route('admin.products.index'));

    expect(Product::where('name', 'Produk Baru')->value('category'))->toBe('Minuman Dingin');
});

test('memilih "+ Buat kategori baru" tanpa mengetik namanya ditolak', function (mixed $namaBaru) {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), [
            ...($this->isian)(KategoriProdukService::PILIHAN_BARU),
            'category_baru' => $namaBaru,
        ])
        ->assertSessionHasErrors('category');

    // Jangan sampai tersimpan sebagai kategori bernama "__baru__".
    expect(Product::count())->toBe(0);
})->with([
    'kosong' => [''],
    'cuma spasi' => ['   '],
    'isian aneh' => [['a', 'b']],
]);

test('kategori yang beda huruf besar-kecilnya ikut kategori yang sudah ada', function () {
    ($this->buatProduk)('Sembako');

    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), ($this->isian)('  sembako '))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::where('name', 'Produk Baru')->value('category'))->toBe('Sembako')
        ->and(Product::distinct()->count('category'))->toBe(1);
});

test('kategori baru dirapikan spasinya', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), ($this->isian)("  Minuman \t  Dingin  "))
        ->assertRedirect(route('admin.products.index'));

    expect(Product::where('name', 'Produk Baru')->value('category'))->toBe('Minuman Dingin');
});

test('kategori yang isinya cuma spasi ditolak', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), ($this->isian)('   '))
        ->assertSessionHasErrors('category');

    expect(Product::count())->toBe(0);
});

test('satu-satunya produk di sebuah kategori bisa dibetulkan penulisannya', function () {
    // Kalau produk yang sedang diubah ikut dibandingkan, "Sembako" akan selalu
    // dikembalikan ke "sembako" miliknya sendiri dan tidak pernah bisa dibetulkan.
    $produk = ($this->buatProduk)('sembako');

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $produk), ($this->isian)('Sembako'))
        ->assertRedirect(route('admin.products.index'));

    expect($produk->fresh()->category)->toBe('Sembako');
});

test('mengubah produk tetap mengikuti kategori milik produk lain', function () {
    ($this->buatProduk)('Sembako', 'Beras');
    $gula = ($this->buatProduk)('Bahan Kue', 'Gula');

    $this->actingAs($this->admin)
        ->put(route('admin.products.update', $gula), ($this->isian)('SEMBAKO'))
        ->assertRedirect(route('admin.products.index'));

    expect($gula->fresh()->category)->toBe('Sembako');
});
