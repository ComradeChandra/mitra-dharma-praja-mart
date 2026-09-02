<?php

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\User;

/**
 * Halaman "Masuk" terpadu + menu profil di header.
 *
 * Yang dijaga di sini:
 * 1. Ketiga peran bisa masuk dari satu halaman yang sama.
 * 2. Pintu pengurus TIDAK dipajang ke pengunjung biasa di header.
 * 3. Setelah masuk, tautan login berganti jadi identitas penggunanya.
 */
beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'password' => 'anggota123',
        'is_active' => true,
    ]);

    $this->opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $this->admin = User::factory()->create(['name' => 'Admin Koperasi']);
});

test('halaman masuk terpadu menyediakan ketiga pilihan peran', function () {
    $this->get(route('masuk'))
        ->assertOk()
        ->assertSee('Anggota koperasi')
        ->assertSee('Non-anggota (lewat OPD)')
        ->assertSee('Pengurus koperasi');
});

test('nama anggota muncul sebagai pilihan, tidak perlu diketik', function () {
    $this->get(route('masuk'))
        ->assertSee('Siti Nurhaliza')
        ->assertSee('Dinas Pendidikan');
});

test('anggota bisa masuk lewat halaman terpadu', function () {
    $this->post(route('member.login.store'), [
        'member_code' => '0001 A',
        'password' => 'anggota123',
    ])->assertRedirect(route('member.dashboard', absolute: false));

    $this->assertAuthenticatedAs($this->anggota, 'member');
});

test('katalog TIDAK memajang pintu masuk pengurus ke pengunjung biasa', function () {
    // Dulu ada 3 tautan berjajar di header, termasuk "Login Admin" yang
    // seharusnya tidak ditawarkan terang-terangan ke semua orang.
    $this->get(route('catalog.index'))
        ->assertOk()
        ->assertDontSee('Login Admin')
        ->assertDontSee('Login Anggota')
        ->assertSee('Masuk');
});

test('setelah anggota masuk, header menampilkan namanya bukan tautan login', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('catalog.index'))
        ->assertSee('Siti Nurhaliza')
        ->assertSee('Beranda Saya');
});

test('setelah non-anggota masuk, header menampilkan nama OPD-nya', function () {
    $this->withSession(['non_member_opd_id' => $this->opd->id])
        ->get(route('catalog.index'))
        ->assertSee('Dinas Pendidikan');
});

test('setelah pengurus masuk, header menampilkan pintasan ke area kelola', function () {
    $this->actingAs($this->admin)
        ->get(route('catalog.index'))
        ->assertSee('Admin Koperasi')
        ->assertSee('Kelola Koperasi');
});

test('halaman katalog tidak lagi menyebut instansi yang salah', function () {
    // Koperasinya BUKAN "Koperasi Pegawai Pemkot Cimahi" (dikoreksi user,
    // 1 Sep 2026) — tulisan itu harus hilang dari seluruh halaman.
    $this->get(route('catalog.index'))
        ->assertDontSee('Koperasi Pegawai')
        ->assertDontSee('Pemkot Cimahi')
        ->assertDontSee('Pemerintah Kota Cimahi');
});

/**
 * Apakah input dengan id ini dimatikan dari sisi server?
 *
 * Dicek dari atribut `disabled` yang berdiri sendiri, bukan sekadar string
 * "disabled" di mana pun. Semua input di halaman ini punya
 * `x-bind:disabled` buat Alpine, jadi pencocokan apa adanya akan selalu
 * kena. Laravel merender `@disabled` tepat setelah `<input`.
 */
function inputMati(string $html, string $id): bool
{
    preg_match('/<input[^>]*id="'.preg_quote($id, '/').'"[^>]*>/', $html, $cocok);

    return isset($cocok[0]) && str_starts_with($cocok[0], '<input disabled');
}

test('cuma form peran terpilih yang inputnya hidup', function () {
    // Tiga form password dalam satu halaman bikin pengelola kata sandi browser
    // bingung dan memunculkan prompt berulang. Form yang tidak dipilih
    // inputnya dimatikan sejak render server, tidak menunggu Alpine jalan.
    $html = $this->get(route('masuk'))->assertOk()->getContent();

    // Peran bawaannya anggota, jadi kolom passwordnya harus bisa diisi.
    expect(inputMati($html, 'password_anggota'))->toBeFalse();

    // Dua form lainnya dimatikan.
    expect(inputMati($html, 'access_code'))->toBeTrue();
    expect(inputMati($html, 'password_pengurus'))->toBeTrue();
});

test('form peran yang dipilih ulang setelah gagal login ikut hidup', function () {
    // Kalau login pengurus gagal, halaman kembali membawa peran=pengurus,
    // jadi form itulah yang harus hidup, bukan form anggota.
    $html = $this->withSession(['_old_input' => ['peran' => 'pengurus']])
        ->get(route('masuk'))
        ->getContent();

    expect(inputMati($html, 'password_pengurus'))->toBeFalse();
    expect(inputMati($html, 'password_anggota'))->toBeTrue();
});
