{{--
    Panel "Tinjauan Stok" di halaman detail pesanan pengurus (17 Sep 2026).

    Muncul kalau pesanan memuat barang yang jumlahnya melebihi stok tercatat
    dan pengurus belum memutuskan (keputusan_stok = menunggu). Dua pilihan:

    - Setujui  : koperasi belanja lebih, pesanan lanjut apa adanya.
    - Tolak    : jumlah barang disesuaikan ke stok tercatat (atau dihapus kalau
                 stoknya 0), stok kembali, total dihitung ulang.

    Kalau sudah disetujui, panel berganti jadi catatan ringkas. Kalau ditolak,
    ceritanya sudah muncul di x-order.admin-note, jadi di sini tidak apa-apa.

    Aturan lengkapnya di OrderCancellationService (setujuiStok / tolakKarenaStok).

    Props:
    - order : model Order (orderItems sudah dimuat)
--}}
@props(['order'])

@if ($order->menungguTinjauanStok())
    <x-card class="overflow-hidden border-amber-200">
        <div class="px-5 py-3 border-b border-amber-100 bg-amber-50">
            <h3 class="font-semibold text-amber-900 text-sm">Perlu tinjauan stok</h3>
            <p class="text-xs text-amber-700 mt-0.5">
                Ada barang yang dipesan melebihi stok tercatat. Ini pre-order, jadi pesanannya tidak
                ditolak otomatis — tapi mohon diputuskan: koperasi belanja lebih, atau jumlahnya disesuaikan.
            </p>
        </div>

        {{-- Rincian barang yang melebihi stok --}}
        <ul class="divide-y divide-amber-50">
            @foreach ($order->barangMelebihiStok() as $item)
                <li class="flex items-center justify-between gap-4 px-5 py-3">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $item->product?->name ?? 'Barang' }}</p>
                    <p class="text-sm text-gray-600 shrink-0">
                        Dipesan {{ $item->quantity }}, stok tercatat {{ $item->stok_saat_pesan }}
                        <span class="text-amber-700 font-medium">(lebih {{ $item->kelebihan() }})</span>
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="px-5 py-4 border-t border-amber-100 space-y-4">
            {{-- Setujui: koperasi belanja lebih --}}
            <form method="POST" action="{{ route('admin.orders.setujui-stok', $order) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800 transition">
                    Setujui — koperasi belanja lebih
                </button>
                <span class="ml-2 text-xs text-gray-400">Isi pesanan tidak berubah.</span>
            </form>

            {{-- Tolak: sesuaikan jumlah ke stok tercatat (alasan opsional, terlihat pemesan) --}}
            <form method="POST" action="{{ route('admin.orders.tolak-stok', $order) }}" class="border-t border-gray-100 pt-4">
                @csrf
                @method('PATCH')
                <label for="alasan-tolak-stok" class="block text-sm font-medium text-gray-700">
                    Tolak — sesuaikan ke stok tercatat
                </label>
                <p class="text-xs text-gray-400 mt-0.5">
                    Jumlah yang melebihi diturunkan ke stok tercatat; barang yang stoknya 0 dihapus. Pemesan melihat catatannya.
                </p>
                <textarea
                    id="alasan-tolak-stok"
                    name="alasan"
                    rows="2"
                    maxlength="255"
                    placeholder="Alasan (opsional), mis. stok di grosir juga terbatas"
                    class="mt-2 w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm"
                >{{ old('alasan') }}</textarea>
                <x-input-error :messages="$errors->get('alasan')" class="mt-1" />
                <button type="submit"
                        class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-amber-300 text-amber-800 text-sm font-medium hover:bg-amber-50 transition">
                    Tolak & sesuaikan jumlah
                </button>
            </form>
        </div>
    </x-card>
@elseif ($order->keputusan_stok === \App\Enums\KeputusanStok::Disetujui)
    {{-- Sudah disetujui: catatan ringkas supaya pengurus lain tahu --}}
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3" role="note">
        <p class="text-sm font-medium text-emerald-800">Kelebihan stok disetujui</p>
        <p class="mt-0.5 text-xs text-emerald-700">
            Pesanan ini melebihi stok tercatat, dan sudah disetujui — koperasi belanja lebih. Isi pesanan tidak diubah.
        </p>
    </div>
@endif
