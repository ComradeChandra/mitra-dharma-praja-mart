{{--
    Halaman profil anggota: kartu anggota + ringkasan belanja tahun ini,
    ubah foto/nomor WhatsApp/alamat, ganti password, dan bantuan.

    Nama lengkap & kode anggota SENGAJA ditampilkan tapi tidak bisa diubah:
    keduanya tetap dikelola pengurus (lihat catatan di Member\ProfileController).
--}}
<x-layouts.member :title="'Profil Saya — ' . config('app.name')">

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Data diri, password, dan bantuan kalau ada yang membingungkan.
                </p>
            </div>

            <x-alert type="success" :message="session('success')" />

            {{-- ========== Kartu anggota ========== --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-600 to-emerald-700 p-6 text-white shadow-sm">
                <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>
                <div class="relative flex items-center gap-4">
                    @if ($member->photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($member->photo_path) }}" alt=""
                             class="h-16 w-16 rounded-full object-cover ring-2 ring-white/70 shrink-0">
                    @else
                        <span class="h-16 w-16 rounded-full bg-white text-teal-700 flex items-center justify-center text-2xl font-semibold shrink-0">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->full_name, 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0">
                        <p class="text-lg font-bold truncate">{{ $member->full_name }}</p>
                        <p class="text-sm text-teal-100">Anggota aktif · {{ $member->member_code }}</p>
                        <p class="text-xs text-teal-200">Terdaftar sejak {{ $member->created_at->translatedFormat('F Y') }}</p>
                    </div>
                </div>

                {{-- Ringkasan tahun ini --}}
                <dl class="relative mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-white/10 px-4 py-3">
                        <dt class="text-xs text-teal-100">Pesanan tahun {{ now()->year }}</dt>
                        <dd class="text-xl font-bold">{{ $jumlahPesananTahunIni }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/10 px-4 py-3">
                        <dt class="text-xs text-teal-100">Belanja tahun {{ now()->year }}</dt>
                        <dd class="text-xl font-bold">Rp{{ number_format($belanjaTahunIni, 0, ',', '.') }}</dd>
                    </div>
                </dl>
                <p class="relative mt-3 text-xs text-teal-100">
                    Belanja dihitung dari pesanan yang harganya sudah pasti.
                    <a href="{{ route('member.dashboard') }}" class="underline underline-offset-2 hover:text-white">Lihat perkiraan SHU di Beranda</a>
                </p>
            </div>

            {{-- ========== Data diri ========== --}}
            <form
                method="POST"
                action="{{ route('member.profile.update') }}"
                enctype="multipart/form-data"
                class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                @csrf
                @method('PATCH')

                {{-- Foto: pratinjau saat ini + tombol ganti --}}
                <div class="flex items-center gap-4">
                    @if ($member->photo_path)
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::url($member->photo_path) }}"
                            alt="Foto {{ $member->full_name }}"
                            class="h-20 w-20 rounded-full object-cover border border-gray-200"
                        >
                    @else
                        <span class="h-20 w-20 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-semibold">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->full_name, 0, 1)) }}
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <x-input-label for="photo" value="Foto Profil" />
                        {{-- Jenisnya disebut satu per satu, bukan image/*: dengan begitu iPhone
                             otomatis mengubah foto HEIC jadi JPEG, yang diterima server. --}}
                        <x-file-input name="photo" accept="image/jpeg,image/png,image/webp" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG, atau WEBP. Foto yang besar otomatis diperkecil. Boleh dikosongkan.</p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    </div>
                </div>

                {{-- Dikelola pengurus, ditampilkan saja biar anggota tahu datanya benar --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400">Nama Lengkap</p>
                        <p class="font-medium text-gray-800">{{ $member->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Kode Anggota</p>
                        <p class="font-medium text-gray-800">{{ $member->member_code }}</p>
                    </div>
                    <p class="sm:col-span-2 text-xs text-gray-400">
                        Nama dan kode anggota dikelola pengurus koperasi. Hubungi pengurus
                        kalau ada yang perlu diperbaiki.
                    </p>
                </div>

                <div>
                    <x-input-label for="whatsapp_number" value="Nomor WhatsApp" />
                    <x-text-input
                        id="whatsapp_number"
                        name="whatsapp_number"
                        type="text"
                        class="block mt-1 w-full"
                        placeholder="Contoh: 628123456789 (tanpa spasi/tanda +)"
                        :value="old('whatsapp_number', $member->whatsapp_number)"
                        required
                    />
                    <p class="mt-1 text-xs text-gray-400">Dipakai pengurus buat mengirim invoice pesanan kamu.</p>
                    <x-input-error :messages="$errors->get('whatsapp_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" value="Alamat" />
                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Contoh: Jl. Kebon Kopi No. 12, RT 03/RW 05, Cimahi Tengah"
                        class="block mt-1 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm text-sm"
                    >{{ old('address', $member->address) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">
                        Dipakai sebagai isian awal alamat pengantaran waktu kamu memesan —
                        tetap bisa diubah per pesanan.
                    </p>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <x-primary-button>Simpan Perubahan</x-primary-button>
                </div>
            </form>

            {{-- ========== Ganti password ========== --}}
            <form
                method="POST"
                action="{{ route('member.profile.password.update') }}"
                class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5"
            >
                @csrf
                @method('PUT')

                <div>
                    <h2 class="font-semibold text-gray-800">Ganti Password</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Password pertama kamu dibuatkan pengurus. Kamu bisa menggantinya sendiri di sini.
                    </p>
                </div>

                <div>
                    <x-input-label for="current_password" value="Password Sekarang" />
                    <x-text-input
                        id="current_password"
                        name="current_password"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="current-password"
                    />
                    <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Password Baru" />
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="new-password"
                    />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Ulangi Password Baru" />
                    <x-text-input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="block mt-1 w-full"
                        required
                        autocomplete="new-password"
                    />
                </div>

                <div class="pt-2">
                    <x-primary-button>Ganti Password</x-primary-button>
                </div>
            </form>

            {{-- ========== Bantuan ========== --}}
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800">Butuh bantuan?</h2>
                <p class="text-sm text-gray-500 mt-0.5">Jawaban untuk pertanyaan yang paling sering ditanyakan.</p>

                {{-- Pintas ke kelompok pertanyaan di halaman Bantuan --}}
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                    @foreach ([
                        'pembayaran' => 'Cara membayar lewat QRIS',
                        'pembatalan' => 'Membatalkan atau mengubah pesanan',
                        'akun' => 'Lupa password & ganti nomor WhatsApp',
                        'anggota' => 'Apa itu perkiraan SHU',
                    ] as $jangkar => $judul)
                        <a href="{{ route('bantuan') }}#{{ $jangkar }}"
                           class="flex items-center justify-between gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-gray-700 hover:border-emerald-300 hover:text-emerald-800 transition">
                            {{ $judul }}
                            <span class="text-gray-300" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <a href="{{ route('bantuan') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-900">Semua pertanyaan (FAQ)</a>
                    {{-- Tidak tampil kalau nomor koperasi belum diisi --}}
                    <x-kontak-pengurus varian="tautan" label="Hubungi pengurus lewat WhatsApp" />
                </div>
            </div>

        </div>
    </div>
</x-layouts.member>
