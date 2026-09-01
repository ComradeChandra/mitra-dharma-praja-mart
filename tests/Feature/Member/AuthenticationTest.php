<?php

use App\Models\Member;

test('halaman login anggota bisa dirender', function () {
    $this->get(route('member.login'))->assertOk();
});

test('anggota bisa login pakai member_code + password yang benar', function () {
    $member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $response = $this->post(route('member.login.store'), [
        'member_code' => '0010 A',
        'password' => 'rahasia123',
    ]);

    $this->assertAuthenticatedAs($member, 'member');
    $response->assertRedirect(route('member.dashboard', absolute: false));
});

test('anggota tidak bisa login pakai password yang salah', function () {
    Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->post(route('member.login.store'), [
        'member_code' => '0010 A',
        'password' => 'salah-password',
    ]);

    $this->assertGuest('member');
});

test('anggota yang dinonaktifkan admin tidak bisa login meski password benar', function () {
    Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Nonaktif',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => false,
    ]);

    $this->post(route('member.login.store'), [
        'member_code' => '0010 A',
        'password' => 'rahasia123',
    ]);

    $this->assertGuest('member');
});

test('anggota bisa logout', function () {
    $member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $response = $this->actingAs($member, 'member')->post(route('member.logout'));

    $this->assertGuest('member');
    $response->assertRedirect(route('catalog.index'));
});

test('tamu yang belum login ditolak dari beranda anggota', function () {
    $this->get(route('member.dashboard'))
        ->assertRedirect(route('member.login'));
});

test('anggota yang sudah login diarahkan keluar dari halaman login (tidak boleh login dobel)', function () {
    $member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    $this->actingAs($member, 'member')
        ->get(route('member.login'))
        ->assertRedirect(route('member.dashboard'));
});

test('password anggota disimpan ter-hash, bukan teks polos', function () {
    $member = Member::create([
        'member_code' => '0010 A',
        'full_name' => 'Anggota Uji',
        'whatsapp_number' => '628123456789',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);

    expect($member->password)->not->toBe('rahasia123');
    expect(\Illuminate\Support\Facades\Hash::check('rahasia123', $member->password))->toBeTrue();
});
