<?php

namespace App\Services;

use App\Models\OpdDepartment;
use Illuminate\Support\Facades\Session;

/**
 * Pengelola sesi non-anggota.
 *
 * Non-anggota tidak memakai guard Laravel karena tidak punya akun personal.
 * Yang dipakai kode akses milik satu OPD, dan sesinya cuma menyimpan id OPD.
 * Nama kunci session-nya ditaruh di sini saja supaya tidak tersebar ke
 * controller, middleware, dan file Blade seperti sebelumnya.
 */
class NonMemberSessionService
{
    /** Nama kunci session. Sengaja private, pemakai cukup lewat method di bawah. */
    private const KUNCI = 'non_member_opd_id';

    /**
     * Daftar id pesanan yang dibuat di sesi ini.
     *
     * KENAPA ADA: kode akses OPD dipakai bersama sekantor, jadi kalau
     * kepemilikan pesanan cuma dicek lewat opd_id, siapa pun yang punya kode
     * kantor bisa membuka pesanan rekannya cuma dengan menaikkan angka di URL.
     * Sejak ada pembayaran QRIS, yang ikut terbuka termasuk bukti transfer
     * berisi nama dan nomor rekening.
     *
     * Non-anggota memang tidak punya akun personal, dan itu keputusan yang
     * sudah disetujui, jadi identitasnya tidak bisa dipakai membatasi. Yang
     * dipakai sesinya: satu orang cuma bisa membuka pesanan yang dia buat
     * sendiri di sesi berjalan.
     */
    private const KUNCI_PESANAN = 'non_member_order_ids';

    /** Tandai OPD ini sedang aktif di sesi berjalan. */
    public function masuk(OpdDepartment $opd): void
    {
        Session::put(self::KUNCI, $opd->id);

        // Sesi baru berarti orang baru. Daftar pesanan sesi sebelumnya tidak
        // boleh ikut terbawa, karena bisa jadi yang memakai komputer ini orang
        // yang berbeda.
        Session::forget(self::KUNCI_PESANAN);
    }

    /** Hapus penanda login dari sesi. */
    public function keluar(): void
    {
        Session::forget(self::KUNCI);
        Session::forget(self::KUNCI_PESANAN);
    }

    /** Catat pesanan yang baru saja dibuat, supaya bisa dibuka lagi di sesi ini. */
    public function catatPesanan(int $orderId): void
    {
        $daftar = Session::get(self::KUNCI_PESANAN, []);
        $daftar[] = $orderId;

        Session::put(self::KUNCI_PESANAN, array_values(array_unique($daftar)));
    }

    /** Pesanan ini dibuat di sesi berjalan atau bukan. */
    public function pesanannya(int $orderId): bool
    {
        return in_array($orderId, Session::get(self::KUNCI_PESANAN, []), true);
    }

    /** Cek sesi saja, tanpa menyentuh database. */
    public function sedangLogin(): bool
    {
        return Session::has(self::KUNCI);
    }

    /** Id OPD di sesi, null kalau tidak ada. */
    public function opdId(): ?int
    {
        $id = Session::get(self::KUNCI);

        return $id === null ? null : (int) $id;
    }

    /**
     * Data OPD yang sedang aktif. Null kalau belum login, atau kalau OPD-nya
     * sudah dihapus admin dan sesinya menunjuk data yang tidak ada lagi.
     */
    public function opd(): ?OpdDepartment
    {
        $id = $this->opdId();

        return $id === null ? null : OpdDepartment::find($id);
    }
}
