<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Services\KontakPengurusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Halaman Admin -> Pengaturan. Untuk sekarang isinya nomor WhatsApp koperasi
 * yang dipakai tombol "Hubungi Pengurus" di sisi anggota & non-anggota.
 */
class SettingController extends Controller
{
    public function __construct(private KontakPengurusService $kontak) {}

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'nomorWhatsApp' => $this->kontak->nomorWhatsApp(),
            // Buat tombol "Coba tautannya", supaya pengurus bisa memastikan
            // nomornya benar sebelum anggota memakainya.
            'tautanUji' => $this->kontak->tautanWhatsApp(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $nomor = $request->validated('whatsapp_number');
        $this->kontak->simpanNomorWhatsApp($nomor);

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', $nomor === null
                ? 'Nomor WhatsApp dikosongkan. Tombol "Hubungi Pengurus" sekarang disembunyikan.'
                : 'Nomor WhatsApp koperasi disimpan.');
    }
}
