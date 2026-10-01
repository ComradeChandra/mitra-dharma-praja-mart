<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Potong & pratinjau foto di x-file-input (1 Okt 2026)
|--------------------------------------------------------------------------
| Foto produk bisa dipotong persegi (sama dengan tampilannya di katalog) dan
| dipratinjau sebelum disimpan. Bukti transfer TIDAK boleh bisa dipotong:
| pengurus mencocokkannya ke mutasi rekening, jadi harus utuh.
| Hitungan potongnya diuji di tests/js/potong-foto.test.mjs.
*/

test('form tambah produk punya jendela potong foto', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.products.create'))
        ->assertOk()
        ->assertSee('inputBerkas({ potong: true, pratinjau: true })', false)
        ->assertSee('Atur foto')
        ->assertSee('Pakai foto ini');
});

test('input berkas biasa (mis. bukti transfer) tidak bisa dipotong', function () {
    $this->blade('<x-file-input name="payment_proof" accept="image/jpeg,image/png" />')
        ->assertSee('inputBerkas({ potong: false, pratinjau: false })', false)
        ->assertDontSee('Atur foto')
        ->assertDontSee('Pakai foto ini');
});

test('pratinjau saja bisa dinyalakan tanpa fitur potong', function () {
    $this->blade('<x-file-input name="photo" :pratinjau="true" />')
        ->assertSee('inputBerkas({ potong: false, pratinjau: true })', false)
        ->assertSee('Pratinjau foto yang dipilih')
        ->assertDontSee('Atur foto');
});
