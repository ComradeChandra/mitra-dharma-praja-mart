{{--
    Saklar on/off (toggle switch) — pengganti checkbox bawaan browser yang
    kelihatan sangat polos. Dipakai buat semua field boolean di form admin
    (is_active, is_fluctuating, has_stock_tracking).

    Checkbox aslinya tetap ada (buat data yang dikirim ke server), cuma
    disembunyikan visualnya (sr-only) dan digantikan 2 elemen span yang
    mengikuti state checkbox lewat CSS ":checked" (utility Tailwind "peer").

    Props:
    - name    : nama field (dikirim ke server)
    - label   : teks di sebelah saklar
    - checked : boolean, status awal (dari old()/model)
    - id      : opsional, default sama dengan name

    Attribute tambahan (mis. x-model="...") otomatis diteruskan ke <input>,
    jadi komponen ini tetap kompatibel dipakai bareng Alpine.js kayak di
    form produk (toggle "harga fluktuatif" & "pelacakan stok").
--}}
@props(['name', 'label', 'checked' => false, 'id' => null])

@php $id = $id ?? $name; @endphp

<label for="{{ $id }}" class="flex items-center gap-3 cursor-pointer select-none w-fit">
    <span class="relative inline-flex items-center shrink-0">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            value="1"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        {{-- Track --}}
        <span class="w-10 h-6 bg-gray-200 peer-checked:bg-emerald-700 rounded-full transition-colors duration-200"></span>
        {{-- Thumb --}}
        <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-4"></span>
    </span>
    <span class="text-sm text-gray-600">{{ $label }}</span>
</label>
