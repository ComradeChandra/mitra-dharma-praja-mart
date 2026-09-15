{{--
    Kartu "Batalkan pesanan" di halaman detail pesanan pengurus
    (admin/orders/show). Dipisah jadi komponen supaya halaman detailnya
    tidak makin panjang.

    Pengurus boleh membatalkan kapan saja (aturannya di
    OrderCancellationService). Kalau pembayarannya sudah berjalan, muncul
    centang wajib bahwa uang pemesan dikembalikan: aplikasi tidak bisa
    mengembalikan uang, jadi minimal pengurus sadar itu harus dilakukan.

    Tidak menampilkan apa pun untuk pesanan yang sudah dibatalkan.

    Props:
    - order : model Order
--}}
@props(['order'])

@php
    $status = $order->payment_status;
    $pembayaranBerjalan = $status !== App\Enums\PaymentStatus::Unpaid;
@endphp

@unless ($order->dibatalkan())
    <x-card class="p-5">
        <h3 class="font-semibold text-gray-800 text-sm">Batalkan pesanan</h3>
        <p class="mt-0.5 text-xs text-gray-500 leading-relaxed">
            Misalnya karena barangnya habis di grosir, atau pemesan keberatan dengan harganya.
            Pesanan tetap tercatat sebagai "Dibatalkan", tidak dihitung di rekap, dan stok barangnya dikembalikan.
        </p>

        <form
            method="POST"
            action="{{ route('admin.orders.cancel', $order) }}"
            class="mt-4 space-y-3"
            data-confirm="Batalkan pesanan ini? Pembatalan tidak bisa diurungkan."
            onsubmit="return confirm(this.dataset.confirm);"
        >
            @csrf
            @method('PATCH')

            <div>
                <x-input-label for="alasan" value="Alasan (boleh dikosongkan, terlihat oleh pemesan)" />
                <x-text-input
                    id="alasan"
                    name="alasan"
                    type="text"
                    maxlength="255"
                    class="mt-1 block w-full"
                    :value="old('alasan')"
                    placeholder="Mis. telur habis di grosir"
                />
                <x-input-error :messages="$errors->get('alasan')" class="mt-2" />
            </div>

            @if ($pembayaranBerjalan)
                <label class="flex items-start gap-2.5 rounded-lg bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                    <input
                        type="checkbox"
                        name="uang_dikembalikan"
                        value="1"
                        required
                        class="mt-0.5 rounded border-amber-300 text-emerald-700 focus:ring-emerald-600"
                    >
                    <span>
                        {{ $status === App\Enums\PaymentStatus::Paid ? 'Pesanan ini sudah lunas.' : 'Pemesan sudah menyatakan membayar.' }}
                        Saya memastikan uangnya dikembalikan ke pemesan.
                    </span>
                </label>
                <x-input-error :messages="$errors->get('uang_dikembalikan')" />
            @endif

            <button
                type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-200 text-red-700 text-sm font-medium hover:bg-red-50 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
            >
                Batalkan pesanan
            </button>
        </form>
    </x-card>
@endunless
