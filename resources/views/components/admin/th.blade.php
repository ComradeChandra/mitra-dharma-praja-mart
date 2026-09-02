{{--
    Judul kolom tabel di dashboard admin. Semua tabel (produk, anggota, OPD,
    periode, pesanan) memakai gaya yang sama, jadi ditaruh di satu tempat.

    Kolom angka biasanya rata kanan: <x-admin.th align="right">Jumlah</x-admin.th>
--}}
@props(['align' => 'left'])

{{-- Nama class ditulis utuh, bukan dirangkai "text-{$align}", karena pemindai
     Tailwind membaca berkas ini sebagai teks biasa dan tidak bisa menebak
     hasil rangkaian di runtime. Kalau dirangkai, class-nya ikut terbuang. --}}
<th {{ $attributes->merge(['class' => 'px-6 py-3 '.($align === 'right' ? 'text-right' : 'text-left').' text-xs font-medium text-gray-500 uppercase tracking-wider']) }}>{{ $slot }}</th>
