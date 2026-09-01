<?php

namespace App\Http\Controllers\NonMember;

use App\Http\Controllers\Controller;
use App\Http\Requests\NonMember\LoginRequest;
use App\Models\OpdDepartment;
use App\Services\NonMemberSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Login"/"logout" non-anggota, terpisah total dari Member/ & Auth/ (guard
 * admin), sesuai isolasi yang diminta CLAUDE.md. Bukan Auth guard beneran
 * (lihat catatan di NonMember\LoginRequest), cuma menyimpan penanda OPD ke
 * session lewat NonMemberSessionService.
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(private NonMemberSessionService $sesiNonAnggota) {}

    /**
     * Form login: dropdown pilih OPD + input kode akses.
     */
    public function create(): View
    {
        $opdDepartments = OpdDepartment::orderBy('name')->get();

        return view('non-member.auth.login', compact('opdDepartments'));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $opd = $request->authenticate();

        $request->session()->regenerate();
        $this->sesiNonAnggota->masuk($opd);

        return redirect()->intended(route('non-member.orders.create', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->sesiNonAnggota->keluar();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog.index');
    }
}
