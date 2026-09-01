<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\OpdDepartment;
use Illuminate\View\View;

/**
 * Halaman "Masuk" terpadu. SATU pintu untuk ketiga jenis pengguna
 * (anggota, non-anggota, pengurus/admin), dipilih lewat dropdown peran.
 *
 * Digabung supaya header cukup punya satu tombol "Masuk". Sebelumnya ada tiga
 * tautan berjajar termasuk pintu admin, yang sebetulnya tidak perlu
 * ditawarkan ke semua pengunjung.
 *
 * Controller ini cuma menampilkan formulirnya. Proses login tetap ditangani
 * controller masing-masing beserta guard dan pembatasan percobaan login yang
 * sudah ada, jadi tidak ada logika keamanan yang ditulis ulang di sini.
 */
class LoginPortalController extends Controller
{
    public function __invoke(): View
    {
        // Daftar anggota & OPD buat dropdown pilihan, sama seperti yang
        // dipakai halaman login masing-masing.
        $members = Member::aktif()
            ->orderBy('full_name')
            ->get(['member_code', 'full_name']);

        $opdDepartments = OpdDepartment::orderBy('name')->get(['id', 'name']);

        return view('auth.masuk', compact('members', 'opdDepartments'));
    }
}
