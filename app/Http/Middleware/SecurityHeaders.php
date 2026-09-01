<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nambahin header HTTP dasar buat pertahanan tambahan (defense in depth) —
 * di luar proteksi yang sudah otomatis dari Laravel (CSRF, escaping Blade,
 * dst). Dipasang global buat semua response, lihat bootstrap/app.php.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Browser dilarang "menebak" tipe file dari isinya (mis. nganggep
        // file upload sebagai HTML/JS), cegah trik MIME-sniffing attack.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Larang halaman ini ditaruh di dalam <iframe> situs lain —
        // cegah serangan "clickjacking" (jebakan klik tersembunyi).
        $response->headers->set('X-Frame-Options', 'DENY');

        // Batasi info URL asal (referrer) yang dikirim ke situs lain saat
        // ada link keluar, jangan bocorin URL penuh (mis. token di query
        // string) ke situs pihak ketiga.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Matiin akses ke fitur browser yang sama sekali tidak dipakai
        // aplikasi ini (kamera, mikrofon, lokasi), kalaupun ada celah XSS
        // suatu saat, penyerang tetap tidak bisa minta akses fitur itu.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
