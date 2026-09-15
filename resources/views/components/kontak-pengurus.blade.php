{{--
    Tombol / tautan "Hubungi Pengurus" lewat WhatsApp. Isi tautannya disiapkan
    App\View\Components\KontakPengurus; berkas ini cuma soal tampilan.
--}}
@if ($varian === 'melayang')
    {{--
        Halaman yang punya tanda data-tanpa-kontak-melayang (form Pesan Produk)
        menyembunyikan tombol ini beserta ruang kosongnya, karena di sana
        tombol melayang menutupi kotak jumlah produk dan tombol kirim.
        Halaman itu memasang tautan biasa sebagai gantinya.
    --}}

    {{-- Ruang kosong di dasar halaman, supaya isi paling bawah (mis. tombol
         "Batalkan pesanan") bisa digulir ke atas tombol ini, tidak tertutup --}}
    <div class="no-print h-20 [body:has([data-tanpa-kontak-melayang])_&]:hidden" aria-hidden="true"></div>

    {{-- z-20: di bawah header (z-40) dan jendela konfirmasi (z-50) --}}
    <a
        href="{{ $tautan }}"
        target="_blank"
        rel="noopener"
        aria-label="Hubungi pengurus lewat WhatsApp"
        title="Hubungi pengurus lewat WhatsApp"
        class="no-print fixed z-20 right-4 bottom-4 sm:right-6 sm:bottom-6
               inline-flex items-center justify-center gap-2 h-14 w-14 sm:w-auto sm:px-5 rounded-full
               bg-emerald-600 text-white shadow-lg shadow-emerald-900/25 ring-4 ring-white/80
               hover:bg-emerald-700 transition
               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700
               [body:has([data-tanpa-kontak-melayang])_&]:hidden"
    >
        <x-ikon-whatsapp class="h-7 w-7 sm:h-5 sm:w-5" />
        {{-- Di HP cukup logonya, supaya tidak menutupi banyak isi halaman --}}
        <span class="hidden sm:inline text-sm font-semibold whitespace-nowrap">Hubungi Pengurus</span>
    </a>
@else
    <a
        href="{{ $tautan }}"
        target="_blank"
        rel="noopener"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm font-medium text-emerald-700 hover:text-emerald-800 transition']) }}
    >
        <x-ikon-whatsapp class="h-4 w-4 shrink-0" />
        {{ $label }}
    </a>
@endif
