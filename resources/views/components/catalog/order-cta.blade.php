{{--
    Tombol "Pesan" di katalog. Tujuan dan tulisannya ditentukan di
    App\View\Components\Catalog\OrderCta; berkas ini cuma soal tampilan.

    Warnanya dibedakan per peran supaya tetap sama dengan sebelum tombol ini
    dijadikan komponen: anggota indigo, non-anggota teal, tamu versi redup.
--}}
@php
    $gaya = match ($peran) {
        'anggota' => 'bg-indigo-600 text-white hover:bg-indigo-700',
        'non-anggota' => 'bg-teal-600 text-white hover:bg-teal-700',
        default => 'bg-indigo-50 text-indigo-500 hover:bg-indigo-100',
    };
@endphp

<a
    href="{{ $href }}"
    @if ($title) title="{{ $title }}" @endif
    {{ $attributes->merge(['class' => 'w-full inline-flex items-center justify-center gap-1.5 py-2 rounded-lg text-sm font-medium transition '.$gaya]) }}
>
    @if ($peran === 'tamu')
        {{-- Ikon gembok: menandakan harus masuk dulu --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="4" y="10" width="16" height="10" rx="1.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3" />
        </svg>
    @else
        {{-- Ikon tas belanja --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M16 10a4 4 0 0 1-8 0" />
        </svg>
    @endif

    {{ $label }}
</a>
