{{--
    Satu pertanyaan di halaman Bantuan. Memakai <details> bawaan browser:
    bisa dibuka-tutup tanpa JavaScript dan terbaca pembaca layar.

    Props:
    - pertanyaan : teks pertanyaan
    Slot: jawabannya
--}}
@props(['pertanyaan'])

<details {{ $attributes->merge(['class' => 'group border-b border-gray-100 last:border-0']) }}>
    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 px-5 py-4 text-sm font-medium text-gray-800 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
        <span>{{ $pertanyaan }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </summary>
    <div class="px-5 pb-4 text-sm text-gray-600 leading-relaxed space-y-2">
        {{ $slot }}
    </div>
</details>
