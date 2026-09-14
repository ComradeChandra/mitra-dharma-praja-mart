{{--
    Cadangan untuk kode 4xx yang tidak punya halaman sendiri, terutama 422.

    Beberapa aksi punya penjaga abort(422, 'pesan...') di OrderService, mis.
    "Pembayaran pesanan ini sudah pernah dinyatakan." Tanpa berkas ini
    Laravel tidak menemukan halaman untuk 422 dan jatuh ke halaman error
    polos. Pesannya sudah ditulis dalam bahasa Indonesia di tempat abort-nya,
    jadi di sini tinggal ditampilkan.
--}}
@extends('errors::minimal')

@section('title', __('Tidak bisa diproses'))
@section('code', $exception->getStatusCode())
@section('message', $exception->getMessage() ?: __('Permintaan ini tidak bisa diproses.'))
