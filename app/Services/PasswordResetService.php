<?php

namespace App\Services;

use App\Enums\PasswordResetStatus;
use App\Models\Member;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Antrean "lupa password" anggota (16 Sep 2026).
 *
 * Alurnya:
 * 1. Anggota yang lupa password memilih namanya di halaman "Lupa password"
 *    lalu mengirim permintaan. Tidak perlu login.
 * 2. Permintaan masuk ke satu antrean yang dilihat SEMUA pengurus (bukan
 *    cuma Admin Utama), jadi siapa pun yang sedang memegang aplikasi bisa
 *    membantu.
 * 3. Pengurus menekan "Buatkan password baru": password acak dibuat,
 *    langsung berlaku, lalu dikirim lewat WhatsApp ke nomor yang TERDAFTAR.
 *
 * Kenapa aman walau siapa pun bisa mengajukan atas nama siapa pun: password
 * barunya tidak pernah ditampilkan ke pengaju, cuma dikirim pengurus ke
 * nomor WhatsApp anggota yang tercatat. Paling buruk, anggota menerima
 * password baru yang tidak ia minta.
 */
class PasswordResetService
{
    /**
     * Huruf & angka untuk password acak. Tanpa yang mirip satu sama lain
     * (0/o, 1/l/i), karena anggota mengetiknya ulang dari WhatsApp.
     */
    private const HURUF = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(private WhatsAppLinkService $tautanWhatsApp) {}

    /**
     * Catat permintaan dari anggota. Kalau anggota ini masih punya permintaan
     * yang menunggu, tidak dibuat baris baru (antrean tidak dobel karena
     * tombolnya ditekan berkali-kali); keterangannya saja yang diperbarui.
     */
    public function ajukan(Member $member, ?string $catatan): PasswordResetRequest
    {
        $menunggu = PasswordResetRequest::menunggu()->where('member_id', $member->id)->first();

        if ($menunggu) {
            if (filled($catatan)) {
                $menunggu->update(['note' => $catatan]);
            }

            return $menunggu;
        }

        return PasswordResetRequest::create([
            'member_id' => $member->id,
            'note' => $catatan,
            'status' => PasswordResetStatus::Menunggu,
        ]);
    }

    /**
     * Buatkan password baru untuk anggota pengaju, lalu tandai selesai.
     * Mengembalikan password polosnya (satu-satunya kesempatan melihatnya).
     *
     * Antrean dibuka banyak pengurus sekaligus, jadi dua orang bisa menekan
     * tombol yang sama hampir bersamaan. Barisnya dikunci dan statusnya
     * dibaca ulang: yang kedua ditolak dengan keterangan siapa yang sudah
     * menanganinya, supaya anggota tidak menerima dua password berbeda.
     */
    public function buatkanPasswordBaru(PasswordResetRequest $permintaan, User $pengurus): string
    {
        return DB::transaction(function () use ($permintaan, $pengurus) {
            $permintaan = PasswordResetRequest::lockForUpdate()->findOrFail($permintaan->id);

            if ($permintaan->status !== PasswordResetStatus::Menunggu) {
                abort(422, $this->kalimatSudahDitangani($permintaan));
            }

            $password = $this->passwordAcak();
            $permintaan->member->update(['password' => $password]);
            $this->tutupSemua($permintaan->member, $pengurus, PasswordResetStatus::Selesai);

            return $password;
        });
    }

    /** Abaikan permintaan (mis. dobel atau bukan dari anggotanya). */
    public function abaikan(PasswordResetRequest $permintaan, User $pengurus): void
    {
        // Update bersyarat: kalau sudah ditangani orang lain, tidak berubah apa-apa
        PasswordResetRequest::whereKey($permintaan->id)
            ->where('status', PasswordResetStatus::Menunggu)
            ->update([
                'status' => PasswordResetStatus::Diabaikan,
                'handled_by' => $pengurus->id,
                'handled_at' => now(),
            ]);
    }

    /**
     * Pengurus mengganti password anggota lewat form Edit Anggota: permintaan
     * yang masih menunggu untuk anggota itu otomatis dianggap selesai, supaya
     * tidak ada staf lain yang membuatkan password lagi.
     */
    public function tutupKarenaPasswordDiganti(Member $member, User $pengurus): void
    {
        $this->tutupSemua($member, $pengurus, PasswordResetStatus::Selesai);
    }

    /**
     * Tautan WhatsApp ke nomor TERDAFTAR anggota, berisi password barunya.
     * Pengurus yang menekan kirim di WhatsApp-nya sendiri.
     */
    public function tautanKirimPassword(Member $member, string $password): string
    {
        $teks = "Halo {$member->full_name}, ini pengurus Koperasi Mitra Dharma Praja.\n\n"
            ."Password baru kamu untuk Mitra Dharma Praja Mart: {$password}\n\n"
            .'Masuk di '.route('masuk')." lalu ganti passwordnya di menu Profil Saya.\n"
            .'Kalau kamu tidak merasa meminta password baru, balas pesan ini ya.';

        return $this->tautanWhatsApp->keNomor($member->whatsapp_number, $teks);
    }

    /**
     * Nomor WhatsApp yang disamarkan, mis. "•••• 7890". Ditunjukkan ke
     * pengaju supaya ia tahu ke nomor mana password akan dikirim, tanpa
     * membocorkan nomor anggota ke siapa pun yang membuka halaman itu.
     */
    public function nomorSamaran(Member $member): string
    {
        return '•••• '.substr(preg_replace('/\D/', '', $member->whatsapp_number), -4);
    }

    /** Jumlah permintaan yang belum ditangani, untuk pengingat di dasbor. */
    public function jumlahMenunggu(): int
    {
        return PasswordResetRequest::menunggu()->count();
    }

    private function tutupSemua(Member $member, User $pengurus, PasswordResetStatus $status): void
    {
        PasswordResetRequest::menunggu()
            ->where('member_id', $member->id)
            ->update([
                'status' => $status,
                'handled_by' => $pengurus->id,
                'handled_at' => now(),
            ]);
    }

    private function kalimatSudahDitangani(PasswordResetRequest $permintaan): string
    {
        $siapa = $permintaan->penangan?->name ?? 'pengurus lain';
        $kapan = $permintaan->handled_at?->translatedFormat('d M Y, H:i');

        return "Permintaan ini sudah ditangani {$siapa}".($kapan ? " pada {$kapan}" : '').'.';
    }

    /** Password 8 karakter acak dari HURUF, memakai random_int (acak kriptografis). */
    private function passwordAcak(): string
    {
        $hasil = '';
        for ($i = 0; $i < 8; $i++) {
            $hasil .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
        }

        return $hasil;
    }
}
