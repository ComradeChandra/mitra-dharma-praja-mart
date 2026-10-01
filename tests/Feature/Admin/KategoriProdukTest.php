<?php

use App\Models\Product;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Kategori produk: saran dari kategori yang sudah ada + perapian isian
| (1 Okt 2026)
|--------------------------------------------------------------------------
| Katalog mengelompokkan produk berdasarkan tulisan kategori yang persis sama,
| jadi "Sembako", "sembako", dan "Sembako " tidak boleh tersimpan sebagai tiga
| kategori berbeda. Form produk menyarankan kategori yang sudah ada (datalist),
| dan server merapikan isiannya sebelum disimpan.
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

test('form tambah dan ubah produk menyarankan kategori yang sudah ada', function () {
    ($this->buatProduk)('Sembako');
    $minuman = ($this->buatProduk)('Minuman');

    foreach ([route('admin.products.create'), route('admin.products.edit', $minuman)] as $alamat) {
        $this->actingAs($this->admin)->get($alamat)
            ->assertOk()
            ->assertSee('list="daftar-kategori"', false)
            ->assertSee('<option value="Minuman"></option>', false)
            ->assertSee('<option value="Sembako"></option>', false);
    }
});

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
