{{--
    Form hapus dengan ikon tempat sampah + konfirmasi browser, dipakai di
    semua tabel index modul admin. Dibungkus <form> sendiri (terpisah dari
    link Edit) karena delete wajib pakai method DELETE.

    Props:
    - action  : URL route destroy
    - confirm : teks konfirmasi yang muncul sebelum submit

    Catatan keamanan: teks $confirm ditaruh di atribut data-confirm (bukan
    langsung disisipkan ke string JS di onsubmit). Blade {{ }} meng-escape
    buat konteks HTML attribute, itu sudah pas & aman buat sini. Kalau dulu
    disisipkan langsung ke dalam tanda kutip JS kayak onsubmit="...('{{ $confirm }}')",
    escaping-nya jadi salah konteks (HTML-escape, bukan JS-escape) — aman
    selama isinya teks statis yang kita tulis sendiri, tapi jadi rawan kalau
    suatu saat $confirm diisi dari data dinamis/input user.
--}}
@props(['action', 'confirm' => 'Yakin mau hapus data ini?'])

<form
    method="POST"
    action="{{ $action }}"
    class="inline"
    data-confirm="{{ $confirm }}"
    onsubmit="return confirm(this.dataset.confirm);"
>
    @csrf
    @method('DELETE')
    <button type="submit" class="inline-flex items-center gap-1 text-red-600 hover:text-red-800">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
        </svg>
        Hapus
    </button>
</form>
