<?php

namespace App\Http\Middleware;

use App\Services\NonMemberSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penjaga halaman non-anggota.
 *
 * Non-anggota bukan guard Laravel (tidak ada akun personal), jadi tidak bisa
 * pakai middleware 'auth'. Penanda "sudah masuk"-nya disimpan di session oleh
 * NonMember\AuthenticatedSessionController lewat NonMemberSessionService.
 *
 * Dicek dua hal, bukan cuma satu: penandanya ada DI SESSION, dan OPD-nya
 * masih benar-benar ada di database. Kalau OPD-nya sudah dihapus admin,
 * sesinya dianggap tidak berlaku lagi dan dibersihkan, supaya tidak ada
 * orang yang tetap bisa memesan atas nama instansi yang sudah tidak terdaftar.
 */
class EnsureNonMemberSession
{
    public function __construct(private NonMemberSessionService $sesiNonAnggota) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->sesiNonAnggota->opd()) {
            $this->sesiNonAnggota->keluar();

            return redirect()->guest(route('non-member.login'));
        }

        return $next($request);
    }
}
