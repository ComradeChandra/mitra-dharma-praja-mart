{{--
    Panel pembayaran QRIS di halaman pesanan, dilihat pemesan.

    Diminta Pak Emir (Zoom 3 Sep 2026): gambar QRIS koperasi ditempel setelah
    pesanan dikirim, lalu status bayarnya kelihatan di dashboard pengurus.

    QRIS-nya statis, bukan payment gateway. Uang masuk langsung ke rekening
    koperasi dan aplikasi tidak pernah diberi tahu, jadi pemesan yang
    menyatakan sudah bayar, lalu pengurus yang mencocokkan ke mutasi.

    Props:
    - order  : model Order
    - action : URL tujuan tombol "Saya sudah bayar"
--}}
@props(['order', 'action', 'buktiUrl' => null])

@php
    $status = $order->payment_status;
    $totalFinal = $order->total_amount !== null;
@endphp

<x-card class="overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
        <h2 class="font-semibold text-gray-800 text-sm">Pembayaran</h2>
        <x-admin.badge :color="$status->color()">{{ $status->label() }}</x-admin.badge>
    </div>

    @if (! $totalFinal)
        {{-- Belum ada angka yang bisa dibayar. Pesanan yang memuat produk
             fluktuatif baru punya total setelah pengurus mengunci harganya. --}}
        <div class="px-5 py-6 text-center">
            <p class="text-sm text-gray-600">Menunggu pengurus memastikan harga</p>
            <p class="mt-1 text-xs text-gray-400 leading-relaxed">
                Pesanan ini memuat barang yang harganya belum pasti. Nominal yang harus
                dibayar muncul di sini setelah pengurus menguncinya.
            </p>
        </div>

    @elseif ($status === App\Enums\PaymentStatus::Paid)
        <div class="px-5 py-6 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12.5 2.5 2.5 4.5-5" />
            </svg>
            <p class="mt-2 text-sm font-semibold text-gray-800">Pembayaran sudah dikonfirmasi</p>
            @if ($order->payment_confirmed_at)
                <p class="mt-1 text-xs text-gray-400">
                    {{ $order->payment_confirmed_at->translatedFormat('d F Y, H:i') }}
                </p>
            @endif
        </div>

    @elseif ($status === App\Enums\PaymentStatus::AwaitingConfirmation)
        <div class="px-5 py-6 text-center">
            <p class="text-sm font-semibold text-gray-800">Menunggu pengurus mencocokkan</p>
            <p class="mt-1 text-xs text-gray-500 leading-relaxed">
                Kamu sudah menyatakan membayar
                @if ($order->paid_declared_at)
                    pada {{ $order->paid_declared_at->translatedFormat('d F Y, H:i') }}.
                @else
                    .
                @endif
                Pengurus akan mencocokkannya dengan rekening koperasi.
            </p>

            {{-- Bukti yang tadi diunggah ditampilkan kembali, supaya pemesan
                 bisa memastikan yang terkirim memang berkas yang benar. --}}
            @if ($order->payment_proof_path)
                <a href="{{ $buktiUrl }}"
                   target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 15 4.5-4.5L12 15l3-3 6 6" />
                    </svg>
                    Lihat bukti yang kamu kirim
                </a>
            @endif
        </div>

    @else
        {{-- Belum dibayar: tampilkan QRIS beserta nominalnya --}}
        <div class="px-5 py-5">
            <p class="text-center text-xs text-gray-500">Nominal yang dibayar</p>
            <p class="text-center text-2xl font-bold text-gray-900 mb-4">
                Rp{{ number_format($order->total_amount, 0, ',', '.') }}
            </p>

            <x-qris-koperasi />

            <p class="mt-3 text-xs text-gray-500 text-center leading-relaxed">
                Pindai dengan aplikasi apa pun yang berlogo QRIS, lalu masukkan nominal di atas.
            </p>

            {{--
                Tombol ini cuma PERNYATAAN dari pemesan, bukan bukti bahwa uangnya
                sudah masuk. Yang menentukan lunas tetap pengurus setelah
                mencocokkan ke rekening.
            --}}
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-5 space-y-3">
                @csrf

                <div>
                    <x-input-label for="payment_proof" value="Bukti transfer (boleh dikosongkan)" />
                    <input
                        id="payment_proof"
                        name="payment_proof"
                        type="file"
                        accept="image/jpeg,image/png"
                        class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                    >
                    <p class="mt-1 text-xs text-gray-400">
                        Kalau dilampirkan, pengurus lebih cepat mencocokkannya. JPG atau PNG, maksimal 2MB.
                    </p>
                    <x-input-error :messages="$errors->get('payment_proof')" class="mt-2" />
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition"
                >
                    Saya sudah bayar
                </button>

                <p class="text-[11px] text-gray-400 text-center leading-relaxed">
                    Pembayaran dianggap lunas setelah dicocokkan pengurus dengan rekening koperasi.
                </p>
            </form>
        </div>
    @endif
</x-card>
