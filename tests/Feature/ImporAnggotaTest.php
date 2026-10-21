<?php

use App\Models\Member;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Impor anggota dari CSV (21 Okt 2026)
|--------------------------------------------------------------------------
| php artisan koperasi:impor-anggota. Yang dijaga: tanpa --simpan tidak ada
| yang tersimpan, aturan sama dengan form Tambah Anggota, kode yang sudah
| ada dilewati (aman dijalankan ulang), baris galat menghentikan penyimpanan,
| dan tiap anggota mendapat password acak sendiri yang benar-benar berlaku.
*/

beforeEach(function () {
    $this->folder = sys_get_temp_dir().DIRECTORY_SEPARATOR.'impor-anggota-'.uniqid();
    mkdir($this->folder);

    // Tulis CSV uji; baris pertama = judul kolom
    $this->csv = function (array $baris): string {
        $path = $this->folder.DIRECTORY_SEPARATOR.'anggota.csv';
        $berkas = fopen($path, 'w');
        fwrite($berkas, "\u{FEFF}"); // BOM seperti hasil ekspor Excel
        fputcsv($berkas, ['kode', 'nama', 'whatsapp', 'alamat'], escape: '');
        foreach ($baris as $b) {
            fputcsv($berkas, $b, escape: '');
        }
        fclose($berkas);

        return $path;
    };
});

afterEach(function () {
    array_map('unlink', glob($this->folder.DIRECTORY_SEPARATOR.'*'));
    rmdir($this->folder);
});

test('tanpa --simpan cuma memeriksa, tidak ada yang tersimpan', function () {
    $berkas = ($this->csv)([['001', 'Tuti Hestiantina', '08172337860', 'Sangkuriang']]);

    $this->artisan('koperasi:impor-anggota', ['berkas' => $berkas])
        ->expectsOutputToContain('Baru: 1')
        ->expectsOutputToContain('belum ada yang disimpan')
        ->assertSuccessful();

    expect(Member::count())->toBe(0);
});

test('dengan --simpan anggota tersimpan dengan password acak yang berlaku', function () {
    $berkas = ($this->csv)([
        ['001', 'Tuti Hestiantina', '0817-2337-860', 'Sangkuriang'],
        ['002', 'Subari Rahman', '+62 857 2286 5999', ''],
    ]);
    $daftar = $this->folder.DIRECTORY_SEPARATOR.'password.csv';

    $this->artisan('koperasi:impor-anggota', ['berkas' => $berkas, '--simpan' => true, '--daftar-password' => $daftar])
        ->expectsOutputToContain('2 anggota baru tersimpan')
        ->assertSuccessful();

    // Nomor WA dirapikan dengan aturan yang sama dengan form
    $tuti = Member::where('member_code', '001')->first();
    expect($tuti->whatsapp_number)->toBe('08172337860')
        ->and($tuti->is_active)->toBeTrue()
        ->and(Member::where('member_code', '002')->value('address'))->toBeNull();

    // Daftar password: satu baris per anggota, password berbeda, dan cocok dengan hash di database
    $baris = array_map('str_getcsv', file($daftar, FILE_IGNORE_NEW_LINES));
    expect($baris)->toHaveCount(3)
        ->and($baris[1][3])->not->toBe($baris[2][3])
        ->and(Hash::check($baris[1][3], $tuti->password))->toBeTrue();
});

test('kode yang sudah ada dilewati, jadi aman dijalankan ulang', function () {
    Member::create(['member_code' => '001', 'full_name' => 'Nama Lama', 'whatsapp_number' => '0811', 'password' => 'rahasia123', 'is_active' => true]);
    $berkas = ($this->csv)([
        ['001', 'Nama Baru', '08172337860', ''],
        ['002', 'Subari Rahman', '085722865999', ''],
    ]);

    $this->artisan('koperasi:impor-anggota', ['berkas' => $berkas, '--simpan' => true, '--daftar-password' => $this->folder.'/p.csv'])
        ->expectsOutputToContain('Dilewati (sudah ada): 1')
        ->assertSuccessful();

    expect(Member::where('member_code', '001')->value('full_name'))->toBe('Nama Lama')
        ->and(Member::count())->toBe(2);
});

test('baris galat menghentikan penyimpanan seluruhnya', function () {
    $berkas = ($this->csv)([
        ['001', 'Tuti Hestiantina', '08172337860', ''],
        ['002', 'Tanpa Nomor', '', ''],
        ['001', 'Kode Dobel', '085722865999', ''],
    ]);

    $this->artisan('koperasi:impor-anggota', ['berkas' => $berkas, '--simpan' => true])
        ->expectsOutputToContain('Galat: 2')
        ->expectsOutputToContain('Masih ada baris galat')
        ->assertFailed();

    expect(Member::count())->toBe(0);
});

test('berkas tanpa kolom wajib ditolak dengan pesan jelas', function () {
    $path = $this->folder.DIRECTORY_SEPARATOR.'salah.csv';
    file_put_contents($path, "kode,nama\n001,Tuti\n");

    $this->artisan('koperasi:impor-anggota', ['berkas' => $path])
        ->expectsOutputToContain('whatsapp')
        ->assertFailed();
});
