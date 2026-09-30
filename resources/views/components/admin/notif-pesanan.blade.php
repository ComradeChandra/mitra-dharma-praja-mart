{{--
    Kotak notifikasi "Ada N pesanan baru" di halaman pengurus. Dipasang SEKALI
    di layouts/app.blade.php, jadi muncul di semua halaman admin.

    Pengecekannya di resources/js/notif-pesanan.js (bertanya berkala ke rute
    admin.orders.baru). Kotaknya tersembunyi sampai ada pesanan baru.

    Props:
    - jedaDetik : jeda pengecekan saat tab sedang dilihat
    - jedaLatarDetik : jeda saat tab sedang tidak dilihat (lebih hemat)
--}}
@props(['jedaDetik' => 10, 'jedaLatarDetik' => 30])

<div
    x-data="notifPesanan({
        url: @js(route('admin.orders.baru')),
        jedaMs: {{ (int) $jedaDetik * 1000 }},
        jedaLatarMs: {{ (int) $jedaLatarDetik * 1000 }},
    })"
    class="no-print fixed inset-x-4 bottom-4 z-50 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-80"
>
    {{-- role="status": pembaca layar ikut mengumumkan pesanan baru tanpa
         memindahkan fokus dari pekerjaan pengurus. --}}
    <div
        x-cloak
        x-show="baru > 0"
        x-transition.opacity
        role="status"
        aria-live="polite"
        class="rounded-xl border border-emerald-200 bg-white p-4 shadow-card-hover"
    >
        <div class="flex items-start gap-3">
            {{-- Ikon lonceng --}}
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">
                    Ada <span x-text="baru"></span> pesanan baru
                </p>
                <p class="mt-0.5 text-xs text-gray-500">Masuk setelah halaman ini dibuka.</p>

                <div class="mt-3 flex items-center gap-2">
                    <a
                        href="{{ route('admin.orders.index') }}"
                        class="inline-flex items-center rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    >
                        Lihat pesanan
                    </a>
                    <button
                        type="button"
                        @click="tutup()"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
