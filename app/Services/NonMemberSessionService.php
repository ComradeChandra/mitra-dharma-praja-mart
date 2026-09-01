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

    /** Tandai OPD ini sedang aktif di sesi berjalan. */
    public function masuk(OpdDepartment $opd): void
    {
        Session::put(self::KUNCI, $opd->id);
    }

    /** Hapus penanda login dari sesi. */
    public function keluar(): void
    {
        Session::forget(self::KUNCI);
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
