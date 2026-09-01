<?php

/**
 * '/' sengaja redirect (bukan render halaman langsung) — tamu/anggota/non-anggota
 * diarahkan ke halaman katalog publik, admin yang sudah login diarahkan ke
 * dashboard admin (lihat routes/web.php).
 */
it('redirects guests from the root url to the public catalog page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('catalog.index'));
});
