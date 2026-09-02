{{--
    Template TEKS invoice (bukan HTML) yang dikirim admin ke WhatsApp pemesan
    (Modul 7 di CLAUDE.md). Sengaja dipisah jadi file Blade sendiri (bukan
    string digabung manual di Service) — biar kalau nanti mau ubah kata-kata
    invoicenya, cukup edit file ini, nggak perlu sentuh kode PHP.

    Dirender via view(...)->render() di WhatsAppInvoiceService::generateInvoiceText(),
    hasilnya string teks polos (WhatsApp baca *teks* sebagai bold, itu bukan
    markdown Blade — itu format bawaan WhatsApp).

    Props:
    - order: model Order (harus sudah eager-load orderItems.product, member, orderPeriod)
--}}
@php
    $namaPemesan = $order->member?->full_name ?? $order->non_member_name;
@endphp
*INVOICE PEMESANAN*
Mitra Dharma Praja Mart

Nama: {{ $namaPemesan }}
Periode: {{ $order->orderPeriod->label }}
Cara terima: {{ $order->delivery_method->label() }}
@if ($order->delivery_address)
Alamat: {{ $order->delivery_address }}
@endif

Rincian Pesanan:
@foreach ($order->orderItems as $item)
{{ $loop->iteration }}. {{ $item->product->name }} — {{ $item->product->formatJumlah($item->quantity) }} x Rp{{ number_format($item->price_at_order, 0, ',', '.') }} = Rp{{ number_format($item->quantity * $item->price_at_order, 0, ',', '.') }}
@endforeach

*Total: Rp{{ number_format($order->total_amount, 0, ',', '.') }}*

Terima kasih sudah berbelanja di koperasi kami 🙏
Mohon dicek kembali rincian di atas ya. Kalau ada yang kurang sesuai, silakan hubungi pengurus koperasi.
