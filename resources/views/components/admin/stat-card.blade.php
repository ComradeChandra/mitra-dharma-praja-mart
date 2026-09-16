{{--
    Kartu statistik dashboard admin — ikon berwarna + angka besar + label.
    Kalau diisi 'href', seluruh kartu jadi link (klik langsung ke halaman
    kelola datanya). Dipakai di resources/views/admin/dashboard.blade.php.

    Props:
    - label : teks kecil di atas angka, contoh "Total Anggota"
    - value : angka/teks yang ditampilkan besar
    - href  : opsional, URL tujuan kalau kartu diklik
    - hint  : opsional, teks kecil tambahan di bawah angka
    - color : 'emerald' | 'amber' | 'teal' | 'violet' — warna badge ikon (default 'emerald')

    Slot:
    - icon : isi dengan path SVG (tanpa tag <svg> pembungkus, itu sudah disiapkan di sini)
--}}
@props(['label', 'value', 'href' => null, 'hint' => null, 'color' => 'emerald'])

@php
    $tag = $href ? 'a' : 'div';

    $colorClasses = match ($color) {
        'amber' => 'bg-amber-50 text-amber-600',
        'teal' => 'bg-teal-50 text-teal-600',
        'violet' => 'bg-violet-50 text-violet-600',
        default => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group block bg-white/95 backdrop-blur-sm rounded-2xl border border-gray-100 shadow-card p-5 ' . ($href ? 'hover:shadow-card-hover hover:-translate-y-0.5 transition duration-200' : '')]) }}
>
    <div class="flex items-center gap-3">
        <span class="shrink-0 h-11 w-11 rounded-lg {{ $colorClasses }} flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                {{ $icon }}
            </svg>
        </span>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-900 leading-tight">{{ $value }}</p>
        </div>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</{{ $tag }}>
