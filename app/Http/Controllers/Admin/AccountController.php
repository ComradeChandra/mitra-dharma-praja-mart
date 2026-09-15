<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin -> Akun Pengurus: Admin Utama menambah, mengubah, dan menonaktifkan
 * akun staf koperasi. Rutenya dijaga Gate "admin-utama".
 *
 * Sengaja TIDAK ada hapus akun. Akun staf yang sudah tidak bertugas cukup
 * dinonaktifkan: catatan "ditangani oleh" (mis. di antrean lupa password)
 * tetap menunjuk orang yang benar.
 */
class AccountController extends Controller
{
    public function index(): View
    {
        // Admin Utama di atas, lalu yang aktif, lalu urut nama
        $akun = User::query()
            ->orderByRaw('role = ? desc', [AdminRole::AdminUtama->value])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.accounts.index', compact('akun'));
    }

    public function create(): View
    {
        return view('admin.accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.accounts.index')
            ->with('success', 'Akun baru ditambahkan. Beri tahu pemiliknya email dan password awalnya.');
    }

    public function edit(User $user): View
    {
        return view('admin.accounts.edit', ['akun' => $user]);
    }

    public function update(UpdateAccountRequest $request, User $user): RedirectResponse
    {
        // Password kosong = tidak diganti, jangan sampai tertimpa string kosong
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.accounts.index')
            ->with('success', 'Akun '.$user->name.' diperbarui.');
    }
}
