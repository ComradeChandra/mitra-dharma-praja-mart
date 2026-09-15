<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan akun pengurus yang dinonaktifkan Admin Utama, walau sesinya
 * masih jalan. Pasangan EnsureMemberIsActive di sisi anggota.
 *
 * Tanpa ini, staf yang sudah tidak bertugas tetap bisa memakai area pengurus
 * sampai sesinya habis sendiri, padahal menonaktifkan akun justru supaya dia
 * tidak bisa masuk lagi.
 *
 * Yang dikeluarkan cuma guard 'web'; sesi anggota di browser yang sama
 * (mis. saat demo) tidak ikut ter-logout.
 */
class EnsureAdminIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $akun = Auth::guard('web')->user();

        if ($akun && ! $akun->is_active) {
            Auth::guard('web')->logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Akun ini sedang dinonaktifkan. Hubungi Admin Utama koperasi kalau ini keliru.']);
        }

        return $next($request);
    }
}
