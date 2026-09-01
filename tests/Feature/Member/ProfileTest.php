<?php

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Profil anggota — ubah foto, nomor WhatsApp, alamat, & password sendiri.
 *
 * Yang dijaga khusus: nama lengkap & kode anggota TIDAK boleh bisa diubah
 * anggota, walaupun field-nya dipaksa kirim lewat form. Itu tetap wewenang
 * pengurus — kalau anggota bebas menulis namanya sendiri, rekapnya jadi kacau
 * (alasan dari transkrip rapat).
 */
beforeEach(function () {
    $this->anggota = Member::create([
        'member_code' => '0001 A',
        'full_name' => 'Siti Nurhaliza',
        'whatsapp_number' => '628111111111',
        'address' => 'Alamat lama',
        'password' => 'rahasia123',
        'is_active' => true,
    ]);
});

test('anggota bisa membuka halaman profilnya', function () {
    $this->actingAs($this->anggota, 'member')
        ->get(route('member.profile.edit'))
        ->assertOk()
        ->assertSee('Siti Nurhaliza')
        ->assertSee('0001 A')
        ->assertSee('Alamat lama');
});

test('tamu ditolak dari halaman profil anggota', function () {
    $this->get(route('member.profile.edit'))->assertRedirect(route('member.login'));
});

test('anggota bisa mengubah nomor WhatsApp & alamatnya', function () {
    $this->actingAs($this->anggota, 'member')
        ->patch(route('member.profile.update'), [
            'whatsapp_number' => '628999888777',
            'address' => 'Alamat baru setelah pindah',
        ])
        ->assertRedirect(route('member.profile.edit'));

    $segar = $this->anggota->fresh();
    expect($segar->whatsapp_number)->toBe('628999888777');
    expect($segar->address)->toBe('Alamat baru setelah pindah');
});

test('anggota TIDAK bisa mengubah nama & kode anggotanya sendiri', function () {
    // Dipaksa kirim lewat form — harus diabaikan total.
    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628111111111',
        'full_name' => 'Nama Karangan Sendiri',
        'member_code' => '9999 Z',
    ]);

    $segar = $this->anggota->fresh();
    expect($segar->full_name)->toBe('Siti Nurhaliza');
    expect($segar->member_code)->toBe('0001 A');
});

test('nomor WhatsApp wajib diisi & hanya boleh angka', function () {
    $this->actingAs($this->anggota, 'member')
        ->patch(route('member.profile.update'), ['whatsapp_number' => ''])
        ->assertSessionHasErrors('whatsapp_number');

    $this->actingAs($this->anggota, 'member')
        ->patch(route('member.profile.update'), ['whatsapp_number' => '+62 812-3456'])
        ->assertSessionHasErrors('whatsapp_number');
});

test('anggota bisa mengunggah foto profil', function () {
    Storage::fake('public');

    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628111111111',
        'photo' => UploadedFile::fake()->image('foto.jpg'),
    ]);

    $path = $this->anggota->fresh()->photo_path;

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('foto lama dihapus waktu diganti foto baru', function () {
    Storage::fake('public');

    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628111111111',
        'photo' => UploadedFile::fake()->image('lama.jpg'),
    ]);
    $fotoLama = $this->anggota->fresh()->photo_path;

    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628111111111',
        'photo' => UploadedFile::fake()->image('baru.jpg'),
    ]);
    $fotoBaru = $this->anggota->fresh()->photo_path;

    expect($fotoBaru)->not->toBe($fotoLama);
    Storage::disk('public')->assertMissing($fotoLama);
    Storage::disk('public')->assertExists($fotoBaru);
});

test('foto lama dipertahankan kalau tidak mengunggah yang baru', function () {
    Storage::fake('public');

    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628111111111',
        'photo' => UploadedFile::fake()->image('foto.jpg'),
    ]);
    $foto = $this->anggota->fresh()->photo_path;

    // Simpan lagi tanpa mengunggah foto — fotonya tidak boleh hilang.
    $this->actingAs($this->anggota, 'member')->patch(route('member.profile.update'), [
        'whatsapp_number' => '628222222222',
    ]);

    expect($this->anggota->fresh()->photo_path)->toBe($foto);
    Storage::disk('public')->assertExists($foto);
});

test('berkas non-gambar ditolak', function () {
    Storage::fake('public');

    $this->actingAs($this->anggota, 'member')
        ->patch(route('member.profile.update'), [
            'whatsapp_number' => '628111111111',
            'photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');
});

test('anggota bisa ganti password sendiri', function () {
    $this->actingAs($this->anggota, 'member')
        ->put(route('member.profile.password.update'), [
            'current_password' => 'rahasia123',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ])
        ->assertRedirect(route('member.profile.edit'));

    expect(Hash::check('PasswordBaru123!', $this->anggota->fresh()->password))->toBeTrue();
});

test('ganti password ditolak kalau password lama salah', function () {
    $this->actingAs($this->anggota, 'member')
        ->put(route('member.profile.password.update'), [
            'current_password' => 'password-salah',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ])
        ->assertSessionHasErrors('current_password');

    // Password lamanya tetap berlaku.
    expect(Hash::check('rahasia123', $this->anggota->fresh()->password))->toBeTrue();
});

test('ganti password ditolak kalau konfirmasinya tidak cocok', function () {
    $this->actingAs($this->anggota, 'member')
        ->put(route('member.profile.password.update'), [
            'current_password' => 'rahasia123',
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'BedaSendiri123!',
        ])
        ->assertSessionHasErrors('password');
});
