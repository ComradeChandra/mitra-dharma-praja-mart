{{--
    Judul halaman yang tampil di bilah atas. Dipakai di dalam slot "header"
    milik layout admin dan anggota.
--}}
<h2 {{ $attributes->merge(['class' => 'font-semibold text-xl text-gray-800 leading-tight']) }}>
    {{ $slot }}
</h2>
