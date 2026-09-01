{{--
    Tombol aksi utama (submit form, "+ Tambah ...").

    Props:
    - disabled : true kalau tombol harus dimatikan. WAJIB lewat prop ini,
                 jangan dioper sebagai atribut biasa — `disabled` itu atribut
                 boolean di HTML, jadi `disabled="false"` pun tetap mematikan
                 tombolnya. Pakai @disabled() supaya atributnya benar-benar
                 tidak ditulis waktu nilainya false.
--}}
@props(['disabled' => false])

<button
    @disabled($disabled)
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700 hover:shadow active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-indigo-600 disabled:hover:shadow-sm',
    ]) }}
>
    {{ $slot }}
</button>
