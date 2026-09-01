<?php

use App\Models\OpdDepartment;

// Beda dari Member/AuthenticationTest: non-anggota BUKAN guard Laravel
// beneran (lihat catatan di EnsureNonMemberSession & NonMember\LoginRequest),
// jadi cek "sudah login" di sini pakai assertSessionHas('non_member_opd_id'),
// bukan assertAuthenticatedAs()/assertGuest().

test('halaman login non-anggota bisa dirender', function () {
    $this->get(route('non-member.login'))->assertOk();
});

test('non-anggota bisa login pakai OPD + kode akses yang benar', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $response = $this->post(route('non-member.login.store'), [
        'opd_department_id' => $opd->id,
        'access_code' => 'opd12345',
    ]);

    // assertSessionHas ada di objek $response (TestResponse), bukan di $this (TestCase).
    $response->assertSessionHas('non_member_opd_id', $opd->id);
    $response->assertRedirect(route('non-member.orders.create', absolute: false));
});

test('non-anggota tidak bisa login pakai kode akses yang salah', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    $response = $this->post(route('non-member.login.store'), [
        'opd_department_id' => $opd->id,
        'access_code' => 'kode-salah',
    ]);

    $response->assertSessionHasErrors('access_code');
    $response->assertSessionMissing('non_member_opd_id');
});

test('non-anggota tidak bisa login kalau OPD belum punya kode akses', function () {
    $opd = OpdDepartment::create(['name' => 'OPD Belum Disetel']);

    $response = $this->post(route('non-member.login.store'), [
        'opd_department_id' => $opd->id,
        'access_code' => 'apa-saja',
    ]);

    $response->assertSessionHasErrors('access_code');
    $response->assertSessionMissing('non_member_opd_id');
});

test('non-anggota bisa logout', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);
    $this->post(route('non-member.login.store'), [
        'opd_department_id' => $opd->id,
        'access_code' => 'opd12345',
    ]);

    $response = $this->post(route('non-member.logout'));

    $response->assertSessionMissing('non_member_opd_id');
    $response->assertRedirect(route('catalog.index'));
});

test('tamu yang belum login ditolak dari form pesan non-anggota', function () {
    $this->get(route('non-member.orders.create'))
        ->assertRedirect(route('non-member.login'));
});

test('kode akses OPD disimpan ter-hash, bukan teks polos', function () {
    $opd = OpdDepartment::create(['name' => 'Dinas Pendidikan', 'access_code' => 'opd12345']);

    expect($opd->access_code)->not->toBe('opd12345');
    expect(\Illuminate\Support\Facades\Hash::check('opd12345', $opd->access_code))->toBeTrue();
});
