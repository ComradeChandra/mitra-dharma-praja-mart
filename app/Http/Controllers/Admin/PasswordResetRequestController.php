<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PasswordResetStatus;
use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Antrean "lupa password" di sisi pengurus. Bisa dibuka SEMUA pengurus,
 * bukan cuma Admin Utama: siapa pun yang sedang memegang aplikasi boleh
 * membantu. Aturannya di PasswordResetService.
 */
class PasswordResetRequestController extends Controller
{
    public function __construct(private PasswordResetService $lupaPassword) {}

    public function index(): View
    {
        $menunggu = PasswordResetRequest::menunggu()->with('member')->oldest()->get();

        // Riwayat singkat, supaya kelihatan siapa menangani apa
        $riwayat = PasswordResetRequest::query()
            ->where('status', '!=', PasswordResetStatus::Menunggu)
            ->with('member', 'penangan')
            ->latest('handled_at')
            ->limit(15)
            ->get();

        return view('admin.password-requests.index', compact('menunggu', 'riwayat'));
    }

    /**
     * Buatkan password baru. Passwordnya ditampilkan SEKALI lewat flash
     * session, bersama tautan WhatsApp untuk mengirimkannya ke anggota.
     */
    public function reset(PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        $member = $passwordResetRequest->member;
        $password = $this->lupaPassword->buatkanPasswordBaru($passwordResetRequest, Auth::user());

        return redirect()
            ->route('admin.password-requests.index')
            ->with('passwordBaru', [
                'nama' => $member->full_name,
                'password' => $password,
                'tautan' => $this->lupaPassword->tautanKirimPassword($member, $password),
            ]);
    }

    public function dismiss(PasswordResetRequest $passwordResetRequest): RedirectResponse
    {
        $this->lupaPassword->abaikan($passwordResetRequest, Auth::user());

        return redirect()
            ->route('admin.password-requests.index')
            ->with('success', 'Permintaan diabaikan.');
    }
}
