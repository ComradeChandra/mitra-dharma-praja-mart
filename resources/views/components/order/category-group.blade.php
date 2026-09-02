{{--
    Kelompok satu kategori di form pemesanan. Datanya disiapkan di
    App\View\Components\Order\CategoryGroup.

    Seluruh kelompok disembunyikan kalau semua produk di dalamnya tersaring
    habis, jadi tidak ada judul kategori yang menggantung tanpa isi.
--}}
<div x-show="adaIsinya({{ Illuminate\Support\Js::from($ringkasan) }}, {{ Illuminate\Support\Js::from($category) }})">
    <div class="px-4 py-2 bg-gray-50 border-y border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
        {{ $category }}
    </div>

    @foreach ($products as $product)
        <x-order.product-row :product="$product" :last="$loop->last" />
    @endforeach
</div>
