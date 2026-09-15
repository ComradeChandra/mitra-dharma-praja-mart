<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Arahkan orang yang baru login ke halaman yang tadi dia coba buka, TAPI
 * hanya kalau halaman itu masih di wilayah perannya sendiri.
 *
 * KENAPA ADA (16 Sep 2026, ketemu saat uji pasang mode produksi): ketiga
 * login tadinya memakai redirect()->intended() apa adanya. Laravel mengingat
 * halaman terlindung terakhir yang dibuka, dari wilayah mana pun. Jadi
 * pengurus yang sempat membuka tautan halaman anggota, lalu login sebagai
 * pengurus, diarahkan ke halaman anggota yang bukan haknya dan kembali
 * dilempar ke halaman login. Satu peramban memang bisa dipakai bergantian
 * (mis. pengurus mencoba tampilan anggota), jadi ini bukan kasus mengada-ada.
 *
 * Dipakai oleh ketiga AuthenticatedSessionController (pengurus, anggota,
 * non-anggota).
 */
trait RedirectsWithinArea
{
    /**
     * @param  string  $awalan  awalan jalur wilayah peran ini, mis. "/admin"
     * @param  string  $cadangan  tujuan kalau tidak ada halaman yang diingat, atau halamannya di wilayah lain
     */
    protected function keTujuanDiWilayahSendiri(Request $request, string $awalan, string $cadangan): RedirectResponse
    {
        // pull, bukan get: halaman yang diingat dipakai sekali lalu dilupakan
        $tujuan = $request->session()->pull('url.intended');
        $jalur = $tujuan ? '/'.ltrim((string) parse_url($tujuan, PHP_URL_PATH), '/') : null;

        if ($jalur !== null && ($jalur === $awalan || str_starts_with($jalur, $awalan.'/'))) {
            return redirect()->to($tujuan);
        }

        return redirect()->to($cadangan);
    }
}
