<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotifikasiPesananService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jawaban JSON untuk notifikasi pesanan baru di halaman pengurus.
 * Dipanggil berkala oleh resources/js/notif-pesanan.js.
 *
 * Sengaja cuma mengembalikan dua angka (id terbaru + jumlah pesanan baru),
 * bukan isi pesanan: permintaan ini datang tiap beberapa detik dari setiap
 * tab admin yang terbuka, jadi harus seringan mungkin.
 */
class PesananBaruController extends Controller
{
    public function __invoke(Request $request, NotifikasiPesananService $notifikasi): JsonResponse
    {
        // "sejak" = id pesanan terbaru yang sudah diketahui browser.
        // Batas atas wajib (aturan project): angka kebesaran tidak boleh
        // sampai ke query database.
        $data = $request->validate([
            'sejak' => ['nullable', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
        ]);

        $sejak = isset($data['sejak']) ? (int) $data['sejak'] : null;

        return response()->json($notifikasi->ringkasan($sejak));
    }
}
