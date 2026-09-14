{{--
    Partial form periode pemesanan, dipakai bareng create.blade.php & edit.blade.php.
    $orderPeriod dikirim dari edit.blade.php buat isi value lama.
--}}
@php
    // Nilai lama dari form yang gagal validasi dicetak ke dalam kode Alpine,
    // jadi dibatasi ke nilai yang sah dan dicetak lewat Js::from. Isian yang
    // dimanipulasi (teks aneh, larik) tidak boleh ikut masuk ke JavaScript.
    $statusLama = old('status', $orderPeriod->status->value ?? 'closed');
    $statusAwal = (is_string($statusLama) ? \App\Enums\OrderPeriodStatus::tryFrom($statusLama) : null)?->value ?? 'closed';
@endphp
<div class="space-y-5" x-data="{ status: {{ \Illuminate\Support\Js::from($statusAwal) }} }">
    <div>
        <x-input-label for="label" value="Label Periode" />
        <x-text-input
            id="label"
            name="label"
            type="text"
            class="block mt-1 w-full"
            placeholder="Contoh: Pemesanan Agustus 2026"
            :value="old('label', $orderPeriod->label ?? '')"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('label')" class="mt-2" />
    </div>

    {{-- Ditumpuk ke bawah di HP: dua date picker berdampingan di layar sempit
         cuma dapat jatah ~160px masing-masing, susah dipencet. --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="start_date" value="Tanggal Mulai" />
            <x-text-input
                id="start_date"
                name="start_date"
                type="date"
                class="block mt-1 w-full"
                :value="old('start_date', isset($orderPeriod) ? $orderPeriod->start_date->format('Y-m-d') : '')"
                required
            />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="end_date" value="Tanggal Selesai" />
            <x-text-input
                id="end_date"
                name="end_date"
                type="date"
                class="block mt-1 w-full"
                :value="old('end_date', isset($orderPeriod) ? $orderPeriod->end_date->format('Y-m-d') : '')"
                required
            />
            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label value="Status" />

        {{-- Segmented control 2 pilihan, lebih jelas & cepat diklik daripada
             dropdown <select> buat pilihan binary kayak gini --}}
        <input type="hidden" name="status" :value="status">
        <div class="mt-1 inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50">
            @foreach (\App\Enums\OrderPeriodStatus::cases() as $statusOption)
                <button
                    type="button"
                    @click="status = {{ \Illuminate\Support\Js::from($statusOption->value) }}"
                    :class="status === {{ \Illuminate\Support\Js::from($statusOption->value) }}
                        ? '{{ $statusOption === \App\Enums\OrderPeriodStatus::Open ? 'bg-emerald-600 text-white' : 'bg-gray-600 text-white' }}'
                        : 'text-gray-600 hover:bg-gray-100'"
                    class="px-4 py-1.5 rounded-md text-sm font-medium transition"
                >
                    {{ $statusOption->label() }}
                </button>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
        <p class="mt-1.5 text-xs text-gray-400">
            Anggota/non-anggota hanya bisa kirim pesanan kalau status periode "Dibuka".
        </p>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>Simpan</x-primary-button>
        <a href="{{ route('admin.order-periods.index') }}">
            <x-secondary-button type="button">Batal</x-secondary-button>
        </a>
    </div>
</div>
