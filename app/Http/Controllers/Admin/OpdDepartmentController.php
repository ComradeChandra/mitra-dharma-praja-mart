<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOpdDepartmentRequest;
use App\Http\Requests\Admin\UpdateOpdDepartmentRequest;
use App\Models\OpdDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD data OPD (Organisasi Perangkat Daerah) untuk admin.
 *
 * Data OPD ini yang dipakai buat dropdown pilihan non-anggota saat memesan —
 * non-anggota tidak boleh mengetik nama instansi manual (lihat CLAUDE.md).
 *
 * Controller ini sengaja tipis (murni orkestrasi): terima request yang sudah
 * divalidasi FormRequest, panggil Model, kirim ke View. Tidak ada logic bisnis
 * kompleks di sini karena CRUD OPD memang sederhana (cuma 1 kolom: nama).
 */
class OpdDepartmentController extends Controller
{
    /**
     * Tampilkan daftar semua OPD, diurutkan berdasarkan nama.
     */
    public function index(): View
    {
        $opdDepartments = OpdDepartment::orderBy('name')->paginate(15);

        return view('admin.opd-departments.index', compact('opdDepartments'));
    }

    /**
     * Tampilkan form tambah OPD baru.
     */
    public function create(): View
    {
        return view('admin.opd-departments.create');
    }

    /**
     * Simpan OPD baru ke database. Validasi sudah otomatis dijalankan
     * oleh StoreOpdDepartmentRequest sebelum method ini dipanggil.
     */
    public function store(StoreOpdDepartmentRequest $request): RedirectResponse
    {
        OpdDepartment::create($request->validated());

        return redirect()
            ->route('admin.opd-departments.index')
            ->with('success', 'OPD baru berhasil ditambahkan.');
    }

    /**
     * Tampilkan form edit OPD. $opdDepartment otomatis diisi Laravel lewat
     * Route Model Binding berdasarkan ID di URL.
     */
    public function edit(OpdDepartment $opdDepartment): View
    {
        return view('admin.opd-departments.edit', compact('opdDepartment'));
    }

    /**
     * Update data OPD yang sudah ada. Kode akses di form edit itu opsional
     * (kosongkan = tidak ganti), pola yang sama dengan password anggota di
     * MemberController::update().
     */
    public function update(UpdateOpdDepartmentRequest $request, OpdDepartment $opdDepartment): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['access_code'] ?? null)) {
            unset($data['access_code']);
        }

        $opdDepartment->update($data);

        return redirect()
            ->route('admin.opd-departments.index')
            ->with('success', 'Data OPD berhasil diperbarui.');
    }

    /**
     * Hapus OPD. Karena kolom orders.opd_id memakai nullOnDelete (lihat
     * migration), riwayat pesanan lama dari OPD ini tidak ikut terhapus —
     * cuma referensi opd_id-nya jadi kosong.
     */
    public function destroy(OpdDepartment $opdDepartment): RedirectResponse
    {
        $opdDepartment->delete();

        return redirect()
            ->route('admin.opd-departments.index')
            ->with('success', 'OPD berhasil dihapus.');
    }
}
