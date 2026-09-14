<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan anggota yang dinonaktifkan pengurus, walau sesinya masih jalan.
 *
 * Pengecekan is_active tadinya cuma ada di form login. Anggota yang sudah
 * login lalu dinonaktifkan admin tetap bisa memesan sampai sesinya habis
 * sendiri (2 jam), padahal menonaktifkan anggota justru dimaksudkan supaya
 * dia tidak bisa memesan lagi.
 *
 * Yang dikeluarkan cuma sesi guard anggota, BUKAN seluruh sesi browser:
 * pengurus yang di browser yang sama sedang login sebagai admin (mis. saat
 * demo) tidak ikut ter-logout.
 */
class EnsureMemberIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $anggota = Auth::guard('member')->user();

        if ($anggota && ! $anggota->is_active) {
            Auth::guard('member')->logout();

            return redirect()
                ->route('member.login')
                ->withErrors(['member_code' => 'Akun kamu sedang dinonaktifkan pengurus. Hubungi pengurus koperasi kalau ini keliru.']);
        }

        return $next($request);
    }
}
