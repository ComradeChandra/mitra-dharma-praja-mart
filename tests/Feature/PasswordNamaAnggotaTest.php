<?php

use App\Enums\PasswordResetStatus;
use App\Models\Member;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\PasswordNamaService;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Password anggota "nama depan + 12345678" (26 Okt 2026)
|--------------------------------------------------------------------------
| php artisan koperasi:password-nama-anggota. Yang dijaga: aturan nama
| (huruf kecil, tanda baca dibuang, inisial dilewati), tanpa --simpan tidak
| ada yang berubah, dengan --simpan anggota benar-benar bisa MASUK lewat
| halaman login (bukan cuma Hash::check), dan antrean lupa password ditutup.
*/

// Anggota uji dengan password lama yang sengaja beda dari pola
function anggotaUji(string $kode, string $nama): Member
{
    return Member::create([
        'member_code' => $kode,
        'full_name' => $nama,
        'whatsapp_number' => '081234567890',
        'password' => 'password-lama',
        'is_active' => true,
    ]);
}

test('aturan nama: huruf kecil, tanda baca dibuang, inisial dilewati', function (string $nama, string $harapan) {
    expect(app(PasswordNamaService::class)->buat($nama))->toBe($harapan);
})->with([
    ['Sri Wahyuni', 'sri12345678'],
    ['Moch. Fajar', 'moch12345678'],
    ['A Dewi Lestari', 'dewi12345678'],
    ['  Budi   Santoso ', 'budi12345678'],
    ['A', 'a12345678'],
]);

test('tanpa --simpan cuma pratinjau, password tidak berubah', function () {
    User::factory()->create();
    $m = anggotaUji('001', 'Sri Wahyuni');

    $this->artisan('koperasi:password-nama-anggota')
        ->expectsOutputToContain('sri12345678')
        ->expectsOutputToContain('belum ada yang diganti')
        ->assertSuccessful();

    expect(Hash::check('password-lama', $m->fresh()->password))->toBeTrue();
});

test('dengan --simpan semua anggota bisa masuk memakai pola baru', function () {
    User::factory()->create();
    anggotaUji('001', 'Sri Wahyuni');
    anggotaUji('002', 'A Dewi Lestari');

    $this->artisan('koperasi:password-nama-anggota', ['--simpan' => true])
        ->expectsOutputToContain('Password 2 anggota sudah diganti')
        ->assertSuccessful();

    // Lewat halaman login sungguhan, supaya kejadian 26 Okt (daftar password
    // yang tidak berlaku) tidak terulang tanpa ketahuan
    $this->post(route('member.login.store'),['member_code' => '002', 'password' => 'dewi12345678'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticated('member');
});

test('permintaan lupa password yang menunggu ikut ditutup atas nama Admin Utama', function () {
    $admin = User::factory()->create();
    $m = anggotaUji('001', 'Sri Wahyuni');
    $permintaan = PasswordResetRequest::create(['member_id' => $m->id, 'status' => PasswordResetStatus::Menunggu]);

    $this->artisan('koperasi:password-nama-anggota', ['--simpan' => true])->assertSuccessful();

    expect($permintaan->fresh()->status)->toBe(PasswordResetStatus::Selesai)
        ->and($permintaan->fresh()->handled_by)->toBe($admin->id);
});

test('tanpa Admin Utama aktif, --simpan ditolak dan tidak ada yang berubah', function () {
    User::factory()->pengurus()->create();
    $m = anggotaUji('001', 'Sri Wahyuni');

    $this->artisan('koperasi:password-nama-anggota', ['--simpan' => true])
        ->expectsOutputToContain('Tidak ada akun Admin Utama')
        ->assertFailed();

    expect(Hash::check('password-lama', $m->fresh()->password))->toBeTrue();
});
