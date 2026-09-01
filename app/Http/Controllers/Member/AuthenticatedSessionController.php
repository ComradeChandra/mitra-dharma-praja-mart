<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\LoginRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login/logout anggota, terpisah dari login admin
 * (App\Http\Controllers\Auth\AuthenticatedSessionController), pakai guard
 * 'member' sendiri supaya sesi admin & anggota tidak saling ganggu.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        // Daftar anggota buat dropdown pilihan nama, anggota tinggal memilih
        // namanya, tidak perlu mengetik kode anggota yang gampang salah ketik
        // (spasi, huruf besar-kecil). Sesuai permintaan pengurus, nama-nama
        // anggota langsung kelihatan untuk dipilih.
        //
        // Cuma anggota AKTIF yang ditawarkan, yang sudah nonaktif tidak perlu
        // muncul di pilihan login.
        $members = Member::aktif()
            ->orderBy('full_name')
            ->get(['member_code', 'full_name']);

        return view('member.auth.login', compact('members'));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('member.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }
}
