<?php

namespace App\Services;

use App\Http\Requests\Admin\StoreMemberRequest;
use App\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Impor data anggota sekaligus dari CSV (21 Okt 2026).
 *
 * Koperasi menyerahkan ±100 anggota dalam bentuk spreadsheet; memasukkannya
 * satu per satu lewat Admin → Anggota terlalu lama. Dijalankan lewat perintah
 * `php artisan koperasi:impor-anggota`.
 *
 * Kolom CSV: kode, nama, whatsapp, alamat (alamat boleh kosong).
 *
 * Aturan:
 * - Divalidasi dengan aturan yang SAMA dengan form Tambah Anggota
 *   (StoreMemberRequest), jadi data impor tidak bisa lebih longgar.
 * - Kode yang sudah ada di database dilewati, tidak ditimpa. Jadi perintahnya
 *   aman dijalankan ulang setelah sebagian baris dibetulkan.
 * - Setiap anggota baru mendapat password acak sendiri (PasswordAcakService),
 *   bukan satu password untuk semua. Daftar passwordnya dikembalikan sekali
 *   untuk diserahkan ke pengurus.
 * - Penyimpanan "semua atau tidak sama sekali": kalau satu baris gagal di
 *   tengah jalan, tidak ada yang tersimpan.
 */
class ImporAnggotaService
{
    public const KOLOM = ['kode', 'nama', 'whatsapp', 'alamat'];

    public const BARU = 'baru';

    public const LEWATI = 'lewati';

    public const GALAT = 'galat';

    public function __construct(private PasswordAcakService $passwordAcak) {}

    /**
     * Periksa semua baris TANPA menyimpan apa pun.
     *
     * @param  list<array<string, string|int>>  $baris  hasil BerkasCsvService::baca()
     * @return list<array{baris: int, kode: string, nama: string, status: string, pesan: string, data: array<string, mixed>}>
     */
    public function periksa(array $baris): array
    {
        // Aturan form Tambah Anggota, kecuali password (dibuat acak) dan
        // status aktif (anggota impor selalu aktif)
        $form = new StoreMemberRequest;
        $aturan = Arr::except($form->rules(), ['password', 'is_active']);

        $kodeDiBerkas = [];
        $hasil = [];

        foreach ($baris as $b) {
            $data = [
                'member_code' => $b['kode'],
                'full_name' => $b['nama'],
                'whatsapp_number' => StoreMemberRequest::rapikanNomorWhatsApp($b['whatsapp']),
                'address' => $b['alamat'] !== '' ? $b['alamat'] : null,
            ];
            $hasil[] = ['baris' => $b['baris'], 'kode' => $b['kode'], 'nama' => $b['nama'], 'data' => $data]
                + $this->nilai($data, $aturan, $form->messages(), $kodeDiBerkas);
            $kodeDiBerkas[] = mb_strtolower($b['kode']);
        }

        return $hasil;
    }

    /**
     * Simpan baris berstatus BARU. Semua atau tidak sama sekali.
     *
     * @param  list<array{status: string, data: array<string, mixed>}>  $hasilPeriksa
     * @return list<array{kode: string, nama: string, whatsapp: string, password: string}>
     */
    public function simpan(array $hasilPeriksa): array
    {
        return DB::transaction(function () use ($hasilPeriksa) {
            $daftarPassword = [];

            foreach ($hasilPeriksa as $h) {
                if ($h['status'] !== self::BARU) {
                    continue;
                }
                $password = $this->passwordAcak->buat();
                // password di-hash otomatis oleh cast 'hashed' di model Member
                Member::create([...$h['data'], 'password' => $password, 'is_active' => true]);

                $daftarPassword[] = [
                    'kode' => $h['data']['member_code'],
                    'nama' => $h['data']['full_name'],
                    'whatsapp' => $h['data']['whatsapp_number'],
                    'password' => $password,
                ];
            }

            return $daftarPassword;
        });
    }

    /**
     * Tentukan status satu baris: sudah ada (lewati), dobel di berkas, tidak
     * lolos aturan form (galat), atau siap disimpan (baru).
     *
     * @return array{status: string, pesan: string}
     */
    private function nilai(array $data, array $aturan, array $pesan, array $kodeDiBerkas): array
    {
        if (Member::where('member_code', $data['member_code'])->exists()) {
            return ['status' => self::LEWATI, 'pesan' => 'Kode ini sudah ada di aplikasi.'];
        }

        if (in_array(mb_strtolower($data['member_code']), $kodeDiBerkas, true)) {
            return ['status' => self::GALAT, 'pesan' => 'Kode ini muncul lebih dari sekali di berkas.'];
        }

        $validasi = Validator::make($data, $aturan, $pesan);
        if ($validasi->fails()) {
            return ['status' => self::GALAT, 'pesan' => implode(' ', $validasi->errors()->all())];
        }

        return ['status' => self::BARU, 'pesan' => ''];
    }
}
