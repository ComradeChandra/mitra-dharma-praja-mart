<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Password anggota berpola "nama depan + 12345678" (KEPUTUSAN CHANDRA, 26 Okt 2026).
 *
 * KENAPA ADA: daftar password acak hasil impor 21 Okt ternyata tidak berlaku
 * di server, sehingga anggota tidak bisa masuk. Supaya pengurus cukup
 * menjelaskan satu aturan ke semua anggota, password SEMUA anggota diganti
 * dengan pola yang bisa ditebak pemiliknya sendiri, mis.:
 *   "Sri Wahyuni"         → sri12345678
 *   "Moch. Fajar"         → moch12345678 (tanda baca dibuang)
 *   "A Dewi Lestari"      → dewi12345678 (nama depan 1 huruf dilewati)
 * (contoh fiktif; repo publik, jangan menulis nama anggota sungguhan)
 *
 * Risiko yang disadari: nama anggota tampil di dropdown halaman masuk, jadi
 * orang yang tahu polanya bisa masuk sebagai anggota lain. Anggota diminta
 * mengganti password sendiri lewat Profil Saya setelah masuk.
 *
 * Dijalankan lewat `php artisan koperasi:password-nama-anggota`.
 */
class PasswordNamaService
{
    public const AKHIRAN = '12345678';

    public function __construct(private PasswordResetService $lupaPassword) {}

    /** Password untuk satu nama lengkap: kata pertama (≥ 2 huruf), huruf kecil, + AKHIRAN. */
    public function buat(string $namaLengkap): string
    {
        // Pecah per kata, buang semua yang bukan huruf ("Moch." → "Moch")
        $kata = array_values(array_filter(array_map(
            fn (string $k) => mb_strtolower(preg_replace('/[^\p{L}]/u', '', $k)),
            preg_split('/\s+/', trim($namaLengkap)),
        )));

        // Inisial di depan ("R Rina") dilewati; kalau semuanya 1 huruf, pakai yang pertama
        $depan = collect($kata)->first(fn (string $k) => mb_strlen($k) >= 2) ?? ($kata[0] ?? 'anggota');

        return $depan.self::AKHIRAN;
    }

    /**
     * Pratinjau untuk semua anggota, TANPA menyimpan.
     *
     * @return list<array{kode: string, nama: string, password: string}>
     */
    public function pratinjau(): array
    {
        return Member::orderBy('member_code')
            ->get(['member_code', 'full_name'])
            ->map(fn (Member $m) => [
                'kode' => $m->member_code,
                'nama' => $m->full_name,
                'password' => $this->buat($m->full_name),
            ])
            ->all();
    }

    /**
     * Ganti password SEMUA anggota. Semua atau tidak sama sekali.
     * Permintaan lupa password yang masih menunggu ikut ditutup atas nama
     * $oleh, sama seperti saat password diganti lewat Edit Anggota.
     *
     * @return int jumlah anggota yang passwordnya diganti
     */
    public function terapkan(User $oleh): int
    {
        return DB::transaction(function () use ($oleh) {
            $members = Member::all();

            foreach ($members as $member) {
                // di-hash otomatis oleh cast 'hashed' di model Member
                $member->update(['password' => $this->buat($member->full_name)]);
                $this->lupaPassword->tutupKarenaPasswordDiganti($member, $oleh);
            }

            return $members->count();
        });
    }
}
