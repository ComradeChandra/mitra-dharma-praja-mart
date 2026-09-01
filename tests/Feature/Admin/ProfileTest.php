<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Lama',
        'email' => 'admin-lama@mitradharma.test',
        'password' => 'password-lama',
    ]);
});

test('admin bisa lihat halaman profilnya', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('Admin Lama')
        ->assertSee('admin-lama@mitradharma.test');
});

test('admin bisa update nama dan email', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.profile.update'), [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@mitradharma.test',
        ])
        ->assertRedirect(route('admin.profile.edit'));

    expect($this->admin->fresh())
        ->name->toBe('Admin Baru')
        ->email->toBe('admin-baru@mitradharma.test');
});

test('email harus unik, tidak boleh sama dengan admin lain', function () {
    User::factory()->create(['email' => 'sudah-dipakai@mitradharma.test']);

    $this->actingAs($this->admin)
        ->patch(route('admin.profile.update'), [
            'name' => 'Admin Lama',
            'email' => 'sudah-dipakai@mitradharma.test',
        ])
        ->assertSessionHasErrors('email');
});

test('admin boleh simpan ulang emailnya sendiri tanpa dianggap bentrok', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.profile.update'), [
            'name' => 'Admin Lama',
            'email' => 'admin-lama@mitradharma.test',
        ])
        ->assertSessionHasNoErrors();
});

test('admin bisa ganti password dengan password lama yang benar', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.profile.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])
        ->assertRedirect(route('admin.profile.edit'));

    expect(Hash::check('password-baru-123', $this->admin->fresh()->password))->toBeTrue();
});

test('ganti password ditolak kalau password lama salah', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.profile.password.update'), [
            'current_password' => 'password-salah',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password-lama', $this->admin->fresh()->password))->toBeTrue();
});

test('ganti password ditolak kalau konfirmasi tidak cocok', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.profile.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru-123',
            'password_confirmation' => 'tidak-cocok',
        ])
        ->assertSessionHasErrors('password');
});

test('tamu yang belum login ditolak dari halaman profil admin', function () {
    $this->get(route('admin.profile.edit'))
        ->assertRedirect(route('login'));
});
