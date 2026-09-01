<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('admin wajib isi password saat tambah anggota baru', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.members.store'), [
        'member_code' => '0020 A',
        'full_name' => 'Anggota Baru',
        'whatsapp_number' => '628123456789',
        // password sengaja tidak diisi
    ]);

    $response->assertSessionHasErrors('password');
    expect(Member::where('member_code', '0020 A')->exists())->toBeFalse();
});

test('admin bisa tambah anggota baru lengkap dengan password', function () {
    $this->actingAs($this->admin)->post(route('admin.members.store'), [
        'member_code' => '0020 A',
        'full_name' => 'Anggota Baru',
        'whatsapp_number' => '628123456789',
        'password' => 'password-awal',
        'is_active' => '1',
    ])->assertRedirect(route('admin.members.index'));

    $member = Member::where('member_code', '0020 A')->firstOrFail();
    expect(Hash::check('password-awal', $member->password))->toBeTrue();
});

test('edit anggota dengan field password dikosongkan TIDAK mengubah password lama', function () {
    $member = Member::create([
        'member_code' => '0030 A',
        'full_name' => 'Anggota Lama',
        'whatsapp_number' => '628111111111',
        'password' => 'password-lama',
        'is_active' => true,
    ]);
    $hashLama = $member->password;

    $this->actingAs($this->admin)->put(route('admin.members.update', $member), [
        'member_code' => '0030 A',
        'full_name' => 'Anggota Lama (diedit)',
        'whatsapp_number' => '628111111111',
        // password sengaja dikosongkan
        'is_active' => '1',
    ])->assertRedirect(route('admin.members.index'));

    expect($member->fresh()->password)->toBe($hashLama);
});

test('edit anggota dengan field password diisi MENGUBAH password lama', function () {
    $member = Member::create([
        'member_code' => '0030 A',
        'full_name' => 'Anggota Lama',
        'whatsapp_number' => '628111111111',
        'password' => 'password-lama',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)->put(route('admin.members.update', $member), [
        'member_code' => '0030 A',
        'full_name' => 'Anggota Lama',
        'whatsapp_number' => '628111111111',
        'password' => 'password-baru',
        'is_active' => '1',
    ])->assertRedirect(route('admin.members.index'));

    expect(Hash::check('password-baru', $member->fresh()->password))->toBeTrue();
});
