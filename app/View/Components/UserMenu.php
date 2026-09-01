<?php

namespace App\View\Components;

use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\User;
use App\Services\NonMemberSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * Sisi kanan header halaman publik, menyesuaikan siapa yang sedang masuk.
 *
 * Sengaja komponen berbasis CLASS (bukan komponen Blade biasa) karena perlu
 * mencari data OPD ke database. Aturan di CLAUDE.md, file Blade tidak boleh
 * query database sendiri, jadi pencariannya dikerjakan di sini, view-nya
 * tinggal merender apa yang sudah jadi.
 */
class UserMenu extends Component
{
    public ?Member $anggota;

    public ?User $admin;

    public ?OpdDepartment $opdNonAnggota;

    public function __construct(NonMemberSessionService $sesiNonAnggota)
    {
        $this->anggota = Auth::guard('member')->user();
        $this->admin = Auth::guard('web')->user();
        $this->opdNonAnggota = $sesiNonAnggota->opd();
    }

    public function render(): View
    {
        return view('components.user-menu');
    }
}
