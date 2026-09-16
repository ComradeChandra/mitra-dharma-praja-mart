{{--
    Halaman "Tentang Aplikasi", khusus area pengurus (semua peran boleh buka).
    Menampilkan versi, keterangan singkat, dan kredit pembuat.

    Keterangan pembuat & tahun mengikuti berkas LICENSE dan config/koperasi.php
    (bagian 'aplikasi'). Sesuai butir Atribusi di LICENSE, jangan dihapus.
--}}
<x-app-layout :title="'Tentang Aplikasi — ' . config('app.name')">
    <x-slot name="header">
        <x-page-heading>Tentang Aplikasi</x-page-heading>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu identitas aplikasi --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white shadow-sm">
                <div class="absolute inset-0 opacity-[0.08] [background-image:radial-gradient(circle,white_1px,transparent_1px)] [background-size:18px_18px]"></div>
                <div class="relative flex items-center gap-4">
                    <span class="shrink-0 p-2 rounded-2xl bg-white shadow-sm">
                        <x-application-logo class="h-12 w-12" />
                    </span>
                    <div>
                        <p class="text-lg font-bold">Mitra Dharma Praja Mart</p>
                        <p class="text-sm text-teal-100">Versi {{ config('koperasi.aplikasi.versi') }}</p>
                        <p class="text-xs text-teal-200 mt-0.5">Kebersamaan untuk Kesejahteraan</p>
                    </div>
                </div>
            </div>

            {{-- Keterangan singkat --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Tentang aplikasi ini</h3>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    Aplikasi pemesanan (pre-order) kebutuhan harian untuk Koperasi Mitra Dharma Praja.
                    Anggota dan non-anggota memesan dalam satu periode; koperasi berbelanja setelah pesanan
                    terkumpul, lalu mengirim invoice lewat WhatsApp dan menerima pembayaran QRIS.
                </p>
            </x-card>

            {{-- Kredit pembuat & hak cipta --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Pembuat</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Dibuat oleh</dt>
                        <dd class="font-medium text-gray-800 text-right">{{ config('koperasi.aplikasi.pembuat') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Dalam rangka</dt>
                        <dd class="text-gray-700 text-right">{{ config('koperasi.aplikasi.peran_pembuat') }}, {{ config('koperasi.aplikasi.tahun') }}</dd>
                    </div>
                </dl>
                <p class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400 leading-relaxed">
                    Hak cipta &copy; {{ config('koperasi.aplikasi.tahun') }} {{ config('koperasi.aplikasi.pembuat') }}.
                    Koperasi Mitra Dharma Praja diberi izin memakai dan memelihara aplikasi ini untuk keperluan
                    operasionalnya. Ketentuan lengkap ada di berkas LICENSE pada kode sumber.
                </p>
            </x-card>

            {{-- Info teknis, berguna buat yang merawat aplikasi nanti --}}
            <x-card class="p-6">
                <h3 class="font-semibold text-gray-800">Info teknis</h3>
                <p class="text-sm text-gray-400 mt-0.5 mb-3">Untuk pengembang yang merawat aplikasi.</p>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Kerangka</dt>
                        <dd class="text-gray-700 text-right">Laravel {{ Illuminate\Foundation\Application::VERSION }} · PHP {{ PHP_VERSION }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Basis data</dt>
                        <dd class="text-gray-700 text-right">{{ strtoupper(config('database.default')) }}</dd>
                    </div>
                </dl>
            </x-card>

        </div>
    </div>
</x-app-layout>
