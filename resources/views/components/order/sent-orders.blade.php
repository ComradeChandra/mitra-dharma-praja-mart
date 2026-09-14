{{--
    Pesanan yang sudah terkirim di periode ini, di atas form pesan.
    Isinya disiapkan App\View\Components\Order\SentOrders; berkas ini cuma
    tampilan. Tidak dirender sama sekali kalau belum ada pesanan.
--}}
<div class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm">
    <p class="font-medium text-indigo-900">
        Kamu sudah mengirim {{ count($daftar) }} pesanan di periode ini
    </p>

    <ul class="mt-2 space-y-1">
        @foreach ($daftar as $pesanan)
            <li class="flex flex-wrap items-center gap-x-2 text-indigo-900/80">
                <span>Dikirim {{ $pesanan['waktu'] }}</span>
                <span aria-hidden="true">·</span>
                <span class="tabular-nums">{{ $pesanan['total'] }}</span>
                <span aria-hidden="true">·</span>
                <a href="{{ $pesanan['url'] }}" class="font-medium text-indigo-700 underline underline-offset-2 hover:text-indigo-900">Lihat</a>
            </li>
        @endforeach
    </ul>

    <p class="mt-2 text-xs text-indigo-900/70 leading-relaxed">
        Pesanan di atas sudah tercatat. Kalau mengirim lagi dari halaman ini,
        yang terkirim adalah pesanan <strong>baru</strong>, bukan pengganti.
    </p>
</div>
