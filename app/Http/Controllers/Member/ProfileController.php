<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdatePasswordRequest;
use App\Http\Requests\Member\UpdateProfileRequest;
use App\Services\ImageStorageService;
use App\Services\RecapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman profil anggota, ubah foto, nomor WhatsApp, alamat, dan password
 * sendiri.
 *
 * Nama lengkap dan kode anggota tidak bisa diubah di sini, tetap dikelola
 * admin. Alasannya dari rapat: kalau anggota bebas mengetik namanya sendiri,
 * rekapnya jadi kacau karena ada yang menulis nama suaminya.
 */
class ProfileController extends Controller
{
    public function __construct(
        private ImageStorageService $imageStorage,
        private RecapService $recapService,
    ) {}

    public function edit(): View
    {
        $member = Auth::guard('member')->user();

        return view('member.profile.edit', [
            'member' => $member,
            // Ringkasan di kartu anggota. Perkiraan SHU sengaja TIDAK diulang
            // di sini (sudah ada di Beranda); kartunya menautkan ke sana.
            'jumlahPesananTahunIni' => $this->recapService->memberOrderCountForYear($member),
            'belanjaTahunIni' => $this->recapService->memberYearlySpending($member),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $member = Auth::guard('member')->user();

        $member->update([
            ...$request->validated(),
            // Foto lama dihapus & diganti kalau ada unggahan baru; kalau tidak,
            // path lamanya dipertahankan (tidak ditimpa null).
            'photo_path' => $this->imageStorage->replace(
                $request->file('photo'),
                $member->photo_path,
                'member-photos',
            ),
        ]);

        return redirect()
            ->route('member.profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        // Password baru otomatis di-hash lewat cast 'hashed' di model Member,
        // jadi cukup assign string biasa, tidak perlu Hash::make() manual.
        Auth::guard('member')->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('member.profile.edit')
            ->with('success', 'Password berhasil diganti.');
    }
}
