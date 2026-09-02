<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Kerangka halaman untuk area pengurus.
 *
 * Judul halaman dioper lewat prop `title`, mis.
 * <x-app-layout :title="'Data Anggota — ' . config('app.name')">
 *
 * Harus dideklarasikan sebagai parameter konstruktor. Ini komponen berbasis
 * class, jadi atribut yang tidak terdaftar di sini tidak jadi variabel di
 * view-nya — sebelumnya `:title` memang dioper tapi tidak pernah terpakai,
 * dan semua tab pengurus tertulis nama aplikasinya saja.
 */
class AppLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
