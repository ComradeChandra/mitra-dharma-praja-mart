<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StorePasswordResetRequest;
use App\Models\Member;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Halaman "Lupa password" anggota. Tidak mereset apa pun sendiri: cuma
 * mengirim permintaan ke antrean pengurus (lihat PasswordResetService).
 */
class PasswordResetRequestController extends Controller
{
    public function __construct(private PasswordResetService $lupaPassword) {}

    public function create(): View
    {
        // Daftar nama sama dengan dropdown di halaman masuk
        $members = Member::aktif()->orderBy('full_name')->get(['member_code', 'full_name']);

        return view('member.auth.lupa-password', compact('members'));
    }

    public function store(StorePasswordResetRequest $request): RedirectResponse
    {
        $member = Member::where('member_code', $request->validated('member_code'))->firstOrFail();
        $this->lupaPassword->ajukan($member, $request->validated('note'));

        return redirect()
            ->route('member.password-request.create')
            ->with('terkirim', $this->lupaPassword->nomorSamaran($member));
    }
}
