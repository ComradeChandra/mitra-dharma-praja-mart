{{--
    Tab "Anggota | Pengurus" di bagian atas halaman Data Anggota dan halaman
    Akun Pengurus, supaya dua jenis orang yang dikelola ada di satu tempat
    (sebelumnya Akun Pengurus tersembunyi di menu akun pojok kanan atas).

    Hanya tampil untuk Admin Utama: mengelola akun pengurus memang khusus
    Admin Utama (Gate "admin-utama"). Pengurus biasa cuma punya satu tab, jadi
    bilah tab-nya tidak perlu ditampilkan sama sekali.
--}}
@can('admin-utama')
    @php
        $daftarTab = [
            ['label' => 'Anggota', 'href' => route('admin.members.index'), 'aktif' => request()->routeIs('admin.members.*')],
            ['label' => 'Pengurus', 'href' => route('admin.accounts.index'), 'aktif' => request()->routeIs('admin.accounts.*')],
        ];
    @endphp

    {{-- class tambahan (mis. jarak bawah) dioper dari pemakainya, jadi kalau
         tab tidak tampil, jaraknya ikut tidak ada --}}
    <nav aria-label="Jenis pengguna" {{ $attributes->merge(['class' => 'inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1']) }}>
        @foreach ($daftarTab as $tab)
            <a
                href="{{ $tab['href'] }}"
                @if ($tab['aktif']) aria-current="page" @endif
                @class([
                    'rounded-md px-4 py-1.5 text-sm transition',
                    'bg-white font-semibold text-emerald-700 shadow-sm' => $tab['aktif'],
                    'font-medium text-gray-600 hover:text-gray-900' => ! $tab['aktif'],
                ])
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
@endcan
