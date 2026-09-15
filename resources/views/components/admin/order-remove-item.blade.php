{{--
    Kartu "Hapus barang dari pesanan" di halaman detail pesanan pengurus,
    untuk kasus satu barang tidak bisa dipenuhi (mis. telur habis di grosir)
    tapi barang lain tetap jalan.

    Satu kotak pilihan, bukan tombol hapus di tiap baris barang, supaya
    daftar barangnya tetap bersih dan tidak gampang salah pencet.

    Tampil atau tidaknya diputuskan halaman induk lewat
    OrderCancellationService::alasanTidakBisaHapusBarang() (pesanan batal,
    sudah dibayar, atau tinggal satu barang tidak menampilkan kartu ini).

    Props:
    - order : model Order (orderItems.product sudah dimuat)
--}}
@props(['order'])

<x-card class="p-5">
    <h3 class="font-semibold text-gray-800 text-sm">Hapus barang dari pesanan</h3>
    <p class="mt-0.5 text-xs text-gray-500 leading-relaxed">
        Kalau satu barang tidak bisa dipenuhi, misalnya habis di grosir. Barang lainnya tetap jalan,
        totalnya dihitung ulang, stoknya dikembalikan, dan pemesan melihat catatannya.
    </p>

    <form
        method="POST"
        action="{{ route('admin.orders.remove-item', $order) }}"
        class="mt-4 space-y-3"
        data-confirm="Hapus barang ini dari pesanan? Barang yang sudah dihapus tidak bisa dikembalikan ke pesanan."
        onsubmit="return confirm(this.dataset.confirm);"
    >
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="order_item_id" value="Barang yang dihapus" />
            <select
                id="order_item_id"
                name="order_item_id"
                required
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-600 focus:ring-emerald-600"
            >
                <option value="">Pilih barang…</option>
                @foreach ($order->orderItems as $item)
                    <option value="{{ $item->id }}" @selected((string) old('order_item_id') === (string) $item->id)>
                        {{ $item->product->name }} — {{ $item->quantity }} pcs
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('order_item_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="alasan_hapus" value="Alasan (boleh dikosongkan, terlihat oleh pemesan)" />
            {{-- id berbeda dari kolom alasan di kartu batal, supaya label
                 masing-masing tetap menunjuk ke kotaknya sendiri --}}
            <x-text-input
                id="alasan_hapus"
                name="alasan"
                type="text"
                maxlength="255"
                class="mt-1 block w-full"
                :value="old('order_item_id') ? old('alasan') : ''"
                placeholder="Mis. habis di grosir"
            />
        </div>

        <button
            type="submit"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-200 text-red-700 text-sm font-medium hover:bg-red-50 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
        >
            Hapus barang
        </button>
    </form>
</x-card>
