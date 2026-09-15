{{--
    Tabel "siapa bisa apa": empat tingkatan pemakai aplikasi (Admin Utama,
    Pengurus, Anggota, Non-anggota). Dipakai di halaman Akun Pengurus dan
    Profil Saya (pengurus).

    Isinya HARUS mengikuti kode. Kalau hak akses berubah (Gate "admin-utama",
    rute, dsb), tabel ini ikut diubah.

    Props:
    - sorot : peran yang kolomnya disorot, mis. 'pengurus' (opsional)
--}}
@props(['sorot' => null])

@php
    // Urutan kolom
    $peran = [
        'admin_utama' => 'Admin Utama',
        'pengurus' => 'Pengurus',
        'anggota' => 'Anggota',
        'non_anggota' => 'Non-anggota',
    ];

    // [kelompok => [[fitur, [peran yang bisa]], ...]]
    $baris = [
        'Belanja' => [
            ['Memesan, membayar lewat QRIS, dan menyimpan struk', ['anggota', 'non_anggota']],
            ['Membatalkan pesanan sendiri (periode masih dibuka dan belum dibayar)', ['anggota', 'non_anggota']],
            ['Melihat riwayat pesanan dan perkiraan SHU', ['anggota']],
            ['Mengusulkan produk baru', ['anggota', 'non_anggota']],
            ['Mengganti password sendiri', ['admin_utama', 'pengurus', 'anggota']],
        ],
        'Pekerjaan harian koperasi' => [
            ['Mengelola produk, stok, dan periode pemesanan', ['admin_utama', 'pengurus']],
            ['Mengunci harga, mengirim invoice WhatsApp, dan mengonfirmasi pembayaran', ['admin_utama', 'pengurus']],
            ['Membatalkan pesanan atau menghapus barang dari pesanan', ['admin_utama', 'pengurus']],
            ['Mengelola data anggota dan OPD (termasuk kode akses)', ['admin_utama', 'pengurus']],
            ['Membuatkan password baru untuk anggota yang lupa', ['admin_utama', 'pengurus']],
            ['Menanggapi usulan produk, melihat dasbor dan rekap', ['admin_utama', 'pengurus']],
        ],
        'Khusus Admin Utama' => [
            ['Menambah akun pengurus, mengubah perannya, dan menonaktifkannya', ['admin_utama']],
            ['Mengubah pengaturan koperasi (nomor WhatsApp)', ['admin_utama']],
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th scope="col" class="py-2 pr-4 text-left font-medium text-gray-500">Yang bisa dilakukan</th>
                @foreach ($peran as $kunci => $nama)
                    <th scope="col" class="py-2 px-3 text-center font-semibold whitespace-nowrap {{ $sorot === $kunci ? 'text-emerald-800 bg-emerald-50 rounded-t-lg' : 'text-gray-700' }}">
                        {{ $nama }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($baris as $kelompok => $isi)
                <tr>
                    <th colspan="5" scope="colgroup" class="pt-4 pb-1 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $kelompok }}</th>
                </tr>
                @foreach ($isi as [$fitur, $bisa])
                    <tr class="border-b border-gray-100">
                        <td class="py-2 pr-4 text-gray-700">{{ $fitur }}</td>
                        @foreach ($peran as $kunci => $nama)
                            <td class="py-2 px-3 text-center {{ $sorot === $kunci ? 'bg-emerald-50' : '' }}">
                                @if (in_array($kunci, $bisa, true))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5" />
                                    </svg>
                                    <span class="sr-only">{{ $nama }} bisa</span>
                                @else
                                    <span class="text-gray-300" aria-hidden="true">–</span>
                                    <span class="sr-only">{{ $nama }} tidak bisa</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <p class="mt-3 text-xs text-gray-400 leading-relaxed">
        Pengurus yang juga anggota koperasi memesan lewat akun anggotanya sendiri.
        Non-anggota masuk memakai kode akses OPD yang dipakai bersama sekantor,
        jadi tidak punya password pribadi.
    </p>
</div>
