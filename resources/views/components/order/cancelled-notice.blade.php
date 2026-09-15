{{--
    Pemberitahuan bahwa pesanan ini sudah dibatalkan, beserta oleh siapa,
    kapan, dan alasannya kalau pengurus mengisinya. Dipakai di halaman
    pesanan anggota, non-anggota, dan pengurus.

    Tidak menampilkan apa pun kalau pesanannya masih berlaku, jadi aman
    dipasang tanpa @if di halaman pemanggilnya.

    Props:
    - order : model Order
--}}
@props(['order'])

@if ($order->dibatalkan())
    <div {{ $attributes->merge(['class' => 'flex gap-3 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4']) }} role="status">
        {{-- Ikon lingkaran dicoret: "tidak berlaku lagi" --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" d="m5.7 5.7 12.6 12.6" />
        </svg>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800">Pesanan ini dibatalkan</p>
            <p class="mt-0.5 text-xs text-gray-500">
                Dibatalkan oleh {{ $order->cancelled_by?->label() ?? 'pengurus' }}{{ $order->cancelled_at ? ' pada '.$order->cancelled_at->translatedFormat('d M Y, H:i') : '' }}.
            </p>
            @if ($order->cancellation_reason)
                <p class="mt-2 text-sm text-gray-700">Alasan: {{ $order->cancellation_reason }}</p>
            @endif
        </div>
    </div>
@endif
