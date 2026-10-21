<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Impor produk dari CSV + folder foto (21 Okt 2026)
|--------------------------------------------------------------------------
| php artisan koperasi:impor-produk. Yang dijaga: aturan sama dengan form
| Tambah Produk (fluktuatif boleh tanpa harga jual, stok kosong = tidak
| dilacak), foto disalin ke storage, kategori dirapikan seperti di form,
| nama yang sudah ada dilewati, dan baris galat menghentikan penyimpanan.
*/

beforeEach(function () {
    Storage::fake('public');
    $this->folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'impor-produk-'.uniqid();
    mkdir($this->folder);

    // Foto PNG 1x1 piksel yang sah
    file_put_contents($this->folder.DIRECTORY_SEPARATOR.'beras.png', base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));

    $this->csv = function (array $baris): string {
        $path = $this->folder.DIRECTORY_SEPARATOR.'produk.csv';
        $berkas = fopen($path, 'w');
        fputcsv($berkas, ['kategori', 'nama', 'harga_beli', 'harga_jual', 'fluktuatif', 'stok', 'foto'], escape: '');
        foreach ($baris as $b) {
            fputcsv($berkas, $b, escape: '');
        }
        fclose($berkas);

        return $path;
    };
});

afterEach(function () {
    array_map('unlink', glob($this->folder.DIRECTORY_SEPARATOR.'*'));
    rmdir($this->folder);
});

test('produk tersimpan dengan foto, stok, dan harga sesuai kolomnya', function () {
    $berkas = ($this->csv)([
        ['Sembako', 'Beras Sunvari 5 kg', '85000', '88000', 'tidak', '7', 'beras.png'],
        ['Sembako', 'Telur Ayam Omega 1 kg', '0', '', 'ya', '', ''],
    ]);

    $this->artisan('koperasi:impor-produk', ['berkas' => $berkas, '--foto' => $this->folder, '--simpan' => true])
        ->expectsOutputToContain('2 produk baru tersimpan')
        ->assertSuccessful();

    $beras = Product::where('name', 'Beras Sunvari 5 kg')->first();
    expect((float) $beras->sell_price)->toBe(88000.0)
        ->and($beras->has_stock_tracking)->toBeTrue()
        ->and($beras->stock)->toBe(7)
        ->and($beras->is_active)->toBeTrue();
    Storage::disk('public')->assertExists($beras->image_path);

    // Fluktuatif: tanpa harga jual, stok tidak dilacak, tanpa foto
    $telur = Product::where('name', 'Telur Ayam Omega 1 kg')->first();
    expect($telur->is_fluctuating)->toBeTrue()
        ->and($telur->sell_price)->toBeNull()
        ->and($telur->has_stock_tracking)->toBeFalse()
        ->and($telur->image_path)->toBeNull();
});

test('tanpa --simpan tidak ada produk maupun foto yang tersimpan', function () {
    $berkas = ($this->csv)([['Sembako', 'Beras Sunvari 5 kg', '85000', '88000', 'tidak', '7', 'beras.png']]);

    $this->artisan('koperasi:impor-produk', ['berkas' => $berkas, '--foto' => $this->folder])
        ->expectsOutputToContain('Baru: 1')
        ->assertSuccessful();

    expect(Product::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('kategori dirapikan mengikuti tulisan kategori yang sudah ada', function () {
    Product::create(['category' => 'Sembako', 'name' => 'Beras Lama', 'buy_price' => 60000, 'sell_price' => 65000, 'is_active' => true]);
    $berkas = ($this->csv)([['  sembako ', 'Gula Pasir 1 kg', '17500', '18700', 'tidak', '', '']]);

    $this->artisan('koperasi:impor-produk', ['berkas' => $berkas, '--simpan' => true])->assertSuccessful();

    expect(Product::where('name', 'Gula Pasir 1 kg')->value('category'))->toBe('Sembako');
});

test('nama yang sudah ada dilewati, bukan ditimpa', function () {
    Product::create(['category' => 'Sembako', 'name' => 'Beras Sunvari 5 kg', 'buy_price' => 85000, 'sell_price' => 90000, 'is_active' => true]);
    $berkas = ($this->csv)([['Sembako', 'beras sunvari 5 kg', '85000', '88000', 'tidak', '7', '']]);

    $this->artisan('koperasi:impor-produk', ['berkas' => $berkas, '--simpan' => true])
        ->expectsOutputToContain('Dilewati (sudah ada): 1')
        ->assertSuccessful();

    expect(Product::count())->toBe(1)
        ->and((float) Product::first()->sell_price)->toBe(90000.0);
});

test('baris galat (harga kosong, foto hilang, nama dobel) menghentikan penyimpanan', function () {
    $berkas = ($this->csv)([
        ['Sembako', 'Beras Rojolele 5 kg', '', '', 'tidak', '', ''],
        ['Sembako', 'Beras Cianjur 5 kg', '70000', '75000', 'tidak', '', 'tidak-ada.png'],
        ['Sembako', 'Gula 1 kg', '17500', '18700', 'tidak', '', ''],
        ['Sembako', 'GULA 1 KG', '17500', '18700', 'tidak', '', ''],
    ]);

    $this->artisan('koperasi:impor-produk', ['berkas' => $berkas, '--foto' => $this->folder, '--simpan' => true])
        ->expectsOutputToContain('Galat: 3')
        ->assertFailed();

    expect(Product::count())->toBe(0);
});
