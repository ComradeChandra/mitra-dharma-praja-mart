<?php

/**
 * Hal-hal kecil di 3 halaman login yang gampang hilang tanpa ketahuan kalau
 * layout guest-nya diutak-atik lagi nanti.
 *
 * Kenapa dites: pengguna koperasi ini banyak yang belum terbiasa pakai
 * aplikasi, jadi mereka bergantung pada tombol yang KELIHATAN di halaman —
 * bukan tombol back bawaan browser/HP.
 */
test('ketiga halaman login punya tombol kembali yang kelihatan', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('Kembali ke Katalog');
})->with([
    'login',              // admin
    'member.login',       // anggota
    'non-member.login',   // non-anggota
]);

test('tombol kembali mengarah ke katalog publik', function () {
    $this->get(route('member.login'))
        ->assertSee(route('catalog.index'), false);
});

test('tiap halaman login punya keterangan sendiri, tidak semua "Portal Pengurus"', function () {
    // Dulu ketiganya ikut nulis "Portal Pengurus Koperasi" karena keterangan
    // itu di-hardcode di layout — bikin anggota biasa bingung ("saya kan
    // bukan pengurus, salah halaman ya?").
    $this->get(route('login'))->assertSee('Portal Pengurus Koperasi');

    $this->get(route('member.login'))
        ->assertSee('Masuk sebagai Anggota')
        ->assertDontSee('Portal Pengurus Koperasi');

    $this->get(route('non-member.login'))
        ->assertSee('Masuk sebagai Non-Anggota')
        ->assertDontSee('Portal Pengurus Koperasi');
});
