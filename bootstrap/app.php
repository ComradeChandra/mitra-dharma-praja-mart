<?php

use App\Http\Middleware\EnsureAdminIsActive;
use App\Http\Middleware\EnsureMemberIsActive;
use App\Http\Middleware\EnsureNonMemberSession;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Project ini punya 2 "area" login terpisah: admin (guard 'web') dan
        // anggota (guard 'member', prefix URL '/anggota'). Default Laravel
        // cuma tau 1 halaman login — jadi kita tentukan sendiri tujuan
        // redirect-nya berdasarkan area URL yang sedang diakses.

        // Middleware 'auth' (tamu yang belum login coba buka halaman terproteksi)
        $middleware->redirectGuestsTo(fn ($request) => $request->is('anggota/*')
            ? route('member.login')
            : route('login'));

        // Middleware 'guest' (yang sudah login coba buka halaman login lagi)
        $middleware->redirectUsersTo(fn ($request) => $request->is('anggota/*')
            ? route('member.dashboard')
            : route('admin.dashboard'));

        // Header keamanan dasar (X-Frame-Options, dst) — berlaku di SEMUA
        // response, makanya di-append ke stack global, bukan cuma grup 'web'.
        $middleware->append(SecurityHeaders::class);

        // Alias buat "penjaga sesi" non-anggota (bukan guard Laravel beneran,
        // lihat catatan di EnsureNonMemberSession) — dipakai di routes/non-member.php.
        $middleware->alias([
            'non-member.session' => EnsureNonMemberSession::class,
            'member.aktif' => EnsureMemberIsActive::class,
            // Akun pengurus yang dinonaktifkan saat masih login ikut dikeluarkan
            'admin.aktif' => EnsureAdminIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
