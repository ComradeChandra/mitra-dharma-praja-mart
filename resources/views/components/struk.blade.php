{{--
    Struk pemesanan resmi — dipakai anggota, non-anggota, dan pengurus, jadi
    isinya tidak boleh bergantung pada siapa yang membuka.

    Sengaja TIDAK memakai warna latar atau bayangan tebal: yang penting di sini
    terbaca jelas waktu dicetak hitam-putih, bukan tampil menarik di layar.

    Props:
    - order : model Order (sudah eager-load orderItems.product, member,
              opdDepartment, orderPeriod)
--}}
@props(['order'])

@php
    // Nama & identitas pemesan berbeda antara anggota dan non-anggota, tapi
    // barisnya sama, jadi disiapkan di sini biar tabelnya tidak bercabang.
    $namaPemesan = $order->member?->full_name ?? $order->non_member_name;
    $identitas = $order->member
        ? ['Kode Anggota', $order->member->member_code]
        : ['Instansi (OPD)', $order->opdDepartment?->name ?? '-'];
@endphp

<article class="struk bg-white text-gray-900 mx-auto max-w-2xl border border-gray-300 p-6 sm:p-8">
    {{-- Kepala struk: identitas koperasi --}}
    <header class="flex items-start gap-4 pb-4 border-b-2 border-gray-800">
        <x-application-logo class="h-14 w-14 shrink-0" />
        <div class="min-w-0">
            <p class="font-bold text-base leading-tight">Koperasi Mitra Dharma Praja</p>
            <p class="text-sm text-gray-600">Mitra Dharma Praja Mart</p>
            <p class="text-xs italic text-gray-500 mt-0.5">Kebersamaan untuk Kesejahteraan</p>
        </div>
        <div class="ml-auto text-right shrink-0">
            <p class="text-[11px] uppercase tracking-widest text-gray-500">Struk Pemesanan</p>
            <p class="font-mono font-bold text-sm mt-0.5">{{ $order->nomorStruk() }}</p>
        </div>
    </header>

    {{-- Struk pesanan yang dibatalkan tetap bisa dibuka pengurus, jadi
         ditandai tegas di atas. Garis tebal, bukan warna, supaya tetap
         terbaca waktu dicetak hitam-putih. --}}
    @if ($order->dibatalkan())
        <p class="mt-4 border-2 border-gray-800 px-3 py-2 text-center text-sm font-bold uppercase tracking-widest">
            Pesanan ini dibatalkan{{ $order->cancelled_at ? ' · '.$order->cancelled_at->translatedFormat('d M Y') : '' }}
        </p>
    @endif

    {{-- Keterangan pemesan --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1.5 py-4 text-sm border-b border-gray-300">
        @foreach ([
            ['Pemesan', $namaPemesan],
            $identitas,
            ['Nomor WhatsApp', $order->whatsapp_number],
            ['Periode', $order->orderPeriod->label],
            ['Tanggal Pesan', $order->created_at->translatedFormat('d F Y, H:i')],
            ['Cara Terima', $order->delivery_method->label()],
        ] as [$label, $isi])
            <div class="flex gap-2">
                <span class="text-gray-500 w-32 shrink-0">{{ $label }}</span>
                <span class="font-medium">: {{ $isi }}</span>
            </div>
        @endforeach

        @if ($order->delivery_address)
            <div class="flex gap-2 sm:col-span-2">
                <span class="text-gray-500 w-32 shrink-0">Alamat Antar</span>
                <span class="font-medium">: {{ $order->delivery_address }}</span>
            </div>
        @endif
    </section>

    {{-- Rincian barang --}}
    <table class="w-full text-sm mt-4">
        <thead>
            <tr class="border-b-2 border-gray-800 text-left">
                <th class="py-1.5 w-8 font-semibold">No</th>
                <th class="py-1.5 font-semibold">Produk</th>
                <th class="py-1.5 w-14 text-right font-semibold">Jml</th>
                <th class="py-1.5 w-28 text-right font-semibold">Harga</th>
                <th class="py-1.5 w-28 text-right font-semibold">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr class="border-b border-gray-200 align-top">
                    <td class="py-1.5 text-gray-500">{{ $loop->iteration }}</td>
                    <td class="py-1.5">
                        {{ $item->product->name }}
                        <span class="block text-xs text-gray-500">{{ $item->product->category }}</span>
                    </td>
                    <td class="py-1.5 text-right tabular-nums">{{ $item->quantity }}</td>

                    {{-- Produk fluktuatif belum berharga sampai pengurus
                         memverifikasi, jadi ditulis apa adanya, bukan Rp0 --}}
                    <td class="py-1.5 text-right tabular-nums">
                        {{ $item->price_at_order !== null ? 'Rp'.number_format($item->price_at_order, 0, ',', '.') : 'menyusul' }}
                    </td>
                    <td class="py-1.5 text-right tabular-nums font-medium">
                        {{ $item->price_at_order !== null ? 'Rp'.number_format($item->quantity * $item->price_at_order, 0, ',', '.') : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-800">
                <td colspan="4" class="py-2.5 text-right font-semibold">TOTAL</td>
                <td class="py-2.5 text-right font-bold text-base tabular-nums">
                    {{ $order->total_amount !== null ? 'Rp'.number_format($order->total_amount, 0, ',', '.') : 'Menunggu verifikasi' }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Status & catatan penutup --}}
    <footer class="mt-5 pt-4 border-t border-gray-300 text-xs text-gray-600 space-y-2">
        <div class="flex flex-wrap gap-x-6 gap-y-1">
            <p>
                <span class="text-gray-500">Status pesanan:</span>
                <span class="font-semibold text-gray-900">{{ $order->status->label() }}</span>
            </p>

            {{-- Status bayar ikut dicetak. Sebelum ada pembayaran QRIS, struk
                 tidak menyebut soal uang sama sekali, jadi anggota yang sudah
                 lunas mencetak lembar yang terlihat seperti belum bayar. --}}
            <p>
                <span class="text-gray-500">Pembayaran:</span>
                <span class="font-semibold text-gray-900">{{ $order->payment_status->label() }}</span>
                @if ($order->payment_confirmed_at)
                    <span class="text-gray-500">({{ $order->payment_confirmed_at->translatedFormat('d M Y') }})</span>
                @endif
            </p>
        </div>

        {{-- Ditegaskan supaya tidak dikira sudah lunas. Ini pre-order: barang
             dibelanjakan setelah pesanan terkumpul, tagihan menyusul. --}}
        <p class="leading-relaxed">
            <span class="font-semibold">Catatan:</span>
            {{-- Pesanan batal punya catatannya sendiri; catatan pre-order di
                 cabang @else tidak berlaku lagi untuknya. --}}
            @if ($order->dibatalkan())
                Pesanan ini sudah dibatalkan dan tidak ditagih.
                @if ($order->payment_status !== App\Enums\PaymentStatus::Unpaid)
                    Pembayaran yang sudah masuk dikembalikan oleh pengurus koperasi.
                @endif
                Kalau ada pertanyaan, silakan hubungi pengurus koperasi.
            @else
                @if ($order->payment_status === App\Enums\PaymentStatus::Paid)
                    Pembayaran pesanan ini sudah dicocokkan pengurus dengan rekening koperasi.
                @else
                    Struk ini adalah bukti <span class="font-semibold">pemesanan</span>, bukan bukti pembayaran.
                @endif
                Barang dibelanjakan koperasi setelah pesanan seluruh anggota terkumpul dalam satu periode.
                @if ($order->orderItems->contains(fn ($i) => $i->price_at_order === null))
                    Ada barang yang harganya masih menyusul dan akan dipastikan pengurus saat verifikasi,
                    jadi total di atas belum final.
                @endif
                Kalau ada yang kurang sesuai, silakan hubungi pengurus koperasi.
            @endif
        </p>

        <p class="text-[11px] text-gray-400 pt-1">
            Dicetak {{ now()->translatedFormat('d F Y, H:i') }} · Dokumen ini dibuat otomatis oleh sistem, tanpa tanda tangan basah.
        </p>
    </footer>
</article>
