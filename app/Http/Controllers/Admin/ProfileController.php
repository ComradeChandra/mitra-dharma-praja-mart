<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman profil admin, ganti nama/email, dan ganti password sendiri.
 * Sebelumnya cuma bisa lewat `php artisan tinker`, sekarang ada halamannya.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', ['user' => Auth::user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        Auth::user()->update($request->validated());

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        // Password baru otomatis di-hash lewat cast 'hashed' di model User
        // (lihat app/Models/User.php), jadi cukup assign string biasa di sini
        //, tidak perlu Hash::make() manual.
        Auth::user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Password berhasil diganti.');
    }
}
