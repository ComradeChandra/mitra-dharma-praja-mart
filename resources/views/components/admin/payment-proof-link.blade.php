{{--
    Tautan "Lihat bukti transfer" di halaman detail pesanan pengurus. Dipakai
    di kartu invoice dan di kartu pembayaran pesanan yang dibatalkan.

    Berkasnya ada di disk privat, jadi selalu lewat rute yang memeriksa izin
    (admin.orders.payment-proof), tidak pernah lewat URL berkas langsung.
    Tidak menampilkan apa pun kalau pemesan tidak melampirkan bukti.

    Props:
    - order : model Order
--}}
@props(['order'])

@if ($order->payment_proof_path)
    <a href="{{ route('admin.orders.payment-proof', $order) }}"
       target="_blank" rel="noopener"
       {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm text-emerald-700 hover:text-emerald-900']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 15 4.5-4.5L12 15l3-3 6 6" />
        </svg>
        Lihat bukti transfer
    </a>
@endif
