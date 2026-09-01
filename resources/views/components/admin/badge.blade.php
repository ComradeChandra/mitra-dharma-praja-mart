{{--
    Badge/pill kecil buat status (Aktif/Nonaktif, Dibuka/Ditutup, Fluktuatif,
    dst). Dipakai di semua halaman index modul admin biar warnanya konsisten
    di mana-mana — DRY, tidak copy-paste class Tailwind yang sama berkali-kali.

    Props:
    - color: 'green' | 'gray' | 'amber' | 'red' | 'blue' (default 'gray')
--}}
@props(['color' => 'gray'])

@php
    $colorClasses = match ($color) {
        'green' => 'bg-green-100 text-green-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-red-100 text-red-700',
        'blue' => 'bg-blue-100 text-blue-700',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium $colorClasses"]) }}>
    {{ $slot }}
</span>
