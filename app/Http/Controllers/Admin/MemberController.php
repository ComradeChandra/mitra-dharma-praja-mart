<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD data anggota koperasi untuk admin.
 *
 * Anggota tidak mendaftar sendiri, semua data anggota (kode unik, nama,
 * nomor WhatsApp) diinput di sini oleh admin (lihat CLAUDE.md, Aktor).
 */
class MemberController extends Controller
{
    public function __construct(private PasswordResetService $lupaPassword) {}

    /**
     * Tampilkan daftar semua anggota, diurutkan dari yang terbaru diinput.
     */
    public function index(Request $request): View
    {
        // Kata kunci dari kotak cari. Kalau kosong, scope cari() mengabaikannya.
        $cari = $request->string('cari')->trim()->toString();

        $members = Member::cari($cari)->latest()->paginate(20)->withQueryString();

        // Tautan ke antrean lupa password di kepala halaman, dengan jumlahnya
        $jumlahLupaPassword = $this->lupaPassword->jumlahMenunggu();

        return view('admin.members.index', compact('members', 'cari', 'jumlahLupaPassword'));
    }

    public function create(): View
    {
        return view('admin.members.create');
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        // is_active dari checkbox: kalau tidak dicentang, browser tidak kirim
        // field ini sama sekali, jadi kita isi manual pakai $request->boolean()
        // (otomatis jadi false kalau field-nya tidak ada di request).
        Member::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Anggota baru berhasil ditambahkan.');
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', compact('member'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        // Password di form edit itu opsional (kosongkan = tidak ganti). Ambil
        // semua data tervalidasi TAPI buang key 'password' kalau kosong, supaya
        // password lama anggota tidak ketimpa jadi hash dari string kosong.
        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $member->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Password diganti lewat form ini: permintaan lupa password anggota
        // ini yang masih menunggu ikut ditutup, supaya staf lain tidak
        // membuatkan password lagi.
        if (isset($data['password'])) {
            $this->lupaPassword->tutupKarenaPasswordDiganti($member, $request->user());
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    /**
     * Hapus anggota. Karena orders.member_id & product_requests.member_id
     * memakai nullOnDelete (lihat migration), riwayat pesanan/permintaan
     * anggota ini tidak ikut terhapus, cuma referensi member_id-nya kosong.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }
}
