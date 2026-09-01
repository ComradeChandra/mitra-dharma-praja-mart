{{--
    Link menu di navbar admin. Warnanya didesain buat background BERWARNA
    (gradient teal/hijau — identik warna koperasi), bukan putih — lihat
    resources/views/layouts/navigation.blade.php.
--}}
@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-white text-sm font-semibold leading-5 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-teal-100 hover:text-white hover:border-teal-300 focus:outline-none focus:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
