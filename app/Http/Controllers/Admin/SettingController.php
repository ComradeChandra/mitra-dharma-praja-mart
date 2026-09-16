<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Http\Requests\Admin\UpdateShuRequest;
use App\Services\KontakPengurusService;
use App\Services\ShuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Halaman Admin -> Pengaturan (khusus Admin Utama). Dua bagian:
 * - nomor WhatsApp koperasi untuk tombol "Hubungi Pengurus" (KontakPengurusService)
 * - persentase SHU yang tampil di beranda anggota (ShuService)
 *
 * Tiap bagian formnya sendiri-sendiri supaya validasinya tidak saling
 * mengganggu.
 */
class SettingController extends Controller
{
    public function __construct(
        private KontakPengurusService $kontak,
        private ShuService $shu,
    ) {}

    public function edit(): View
    {
        $persen = $this->shu->persentase();

        return view('admin.settings.edit', [
            'nomorWhatsApp' => $this->kontak->nomorWhatsApp(),
            // Buat tombol "Coba tautannya", supaya pengurus bisa memastikan
            // nomornya benar sebelum anggota memakainya.
            'tautanUji' => $this->kontak->tautanWhatsApp(),
            'shuPersenMin' => $persen['min'],
            'shuPersenMaks' => $persen['maks'],
            'shuSudahDiatur' => $this->shu->sudahDiaturDariAplikasi(),
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

    public function updateShu(UpdateShuRequest $request): RedirectResponse
    {
        $this->shu->simpan(
            (float) $request->validated('shu_persen_min'),
            (float) $request->validated('shu_persen_maks'),
        );

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Persentase SHU disimpan. Beranda anggota langsung mengikuti angka ini.');
    }
}
