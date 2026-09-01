{{--
    Komponen alert/notifikasi, dipakai di semua halaman admin buat nampilin
    pesan sukses/error setelah create/update/delete (dari session flash message).
    Cara pakai: <x-alert type="success" :message="session('success')" />
--}}
@props(['type' => 'success', 'message' => null])

@if ($message)
    @php
        // Warna beda buat tipe sukses vs error, biar admin gampang bedain sekilas
        $classes = $type === 'error'
            ? 'bg-red-50 text-red-700 border-red-200'
            : 'bg-green-50 text-green-700 border-green-200';
    @endphp

    <div {{ $attributes->merge(['class' => "mb-4 px-4 py-3 rounded-md border text-sm $classes"]) }}>
        {{ $message }}
    </div>
@endif
