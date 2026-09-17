<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\KeputusanStok;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Member;
use App\Models\OpdDepartment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPeriod;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logika pemesanan (Modul 3). Dipisah dari controller sesuai aturan service
 * layer di CLAUDE.md.
 *
 * Isinya: membuat pesanan anggota & non-anggota, mengunci harga produk
 * fluktuatif saat admin verifikasi (untuk satu pesanan atau sekaligus semua
 * pesanan di periodenya), mencatat pembayaran, dan menandai invoice sudah
 * dikirim. Pembatalan punya service sendiri, OrderCancellationService.
 */
class OrderService
{
    /**
     * Penyimpan gambar dipakai buat bukti transfer yang dilampirkan pemesan.
     * Dipinjam dari service yang sama yang dipakai foto produk & foto profil,
     * jadi aturan penyimpanannya seragam.
     */
    public function __construct(
        private ImageStorageService $imageStorage,
    ) {}

    /**
     * Buat pesanan baru dari anggota yang sedang login.
     *
     * @param  Member  $member  anggota yang memesan
     * @param  OrderPeriod  $period  periode pemesanan yang sedang dibuka
     * @param  array<int, array{product_id: int, quantity: int}>  $items  daftar produk & jumlah (sudah difilter qty > 0 di Form Request)
     */
    public function createOrder(
        Member $member,
        OrderPeriod $period,
        array $items,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): Order {
        return DB::transaction(function () use ($member, $period, $items, $deliveryMethod, $deliveryAddress) {
            $order = Order::create([
                'order_period_id' => $period->id,
                'user_type' => UserType::Member,
                'member_id' => $member->id,
                'whatsapp_number' => $member->whatsapp_number,
                // Alamat disalin ke pesanan, bukan mengacu ke members.address,
                // supaya perubahan alamat profil tidak mengubah riwayat lama.
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'status' => OrderStatus::Pending,
            ]);

            $this->createOrderItemsFor($order, $items);
            $this->tandaiTinjauanStokBila($order);
            $this->recalculateIfComplete($order);

            return $order->fresh('orderItems');
        });
    }

    /**
     * Buat pesanan dari non-anggota. Bedanya dengan createOrder() cuma di data
     * pemesan: nama dan nomor WA diketik di form, plus opd_id. Pengisian item
     * dan penguncian harganya sama persis.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function createNonMemberOrder(
        OpdDepartment $opd,
        string $nonMemberName,
        string $whatsappNumber,
        OrderPeriod $period,
        array $items,
        DeliveryMethod $deliveryMethod,
        ?string $deliveryAddress,
    ): Order {
        return DB::transaction(function () use ($opd, $nonMemberName, $whatsappNumber, $period, $items, $deliveryMethod, $deliveryAddress) {
            $order = Order::create([
                'order_period_id' => $period->id,
                'user_type' => UserType::NonMember,
                'non_member_name' => $nonMemberName,
                'opd_id' => $opd->id,
                'whatsapp_number' => $whatsappNumber,
                'delivery_method' => $deliveryMethod,
                'delivery_address' => $deliveryAddress,
                'status' => OrderStatus::Pending,
            ]);

            $this->createOrderItemsFor($order, $items);
            $this->tandaiTinjauanStokBila($order);
            $this->recalculateIfComplete($order);

            return $order->fresh('orderItems');
        });
    }

    /**
     * Isi harga produk fluktuatif yang masih kosong, hitung ulang total, lalu
     * tandai pesanan sebagai terverifikasi.
     *
     * Kalau $terapkanKeSemua true, harga yang sama juga diisikan ke pesanan
     * lain di PERIODE YANG SAMA yang memuat barang itu dan harganya masih
     * kosong (15 Sep 2026). Tanpa ini, 30 pesanan telur berarti mengetik harga
     * telur 30 kali. Yang tidak ikut diubah: pesanan yang harganya sudah diisi
     * satu per satu (tidak ditimpa, sesuai keputusan Chandra), pesanan yang
     * sudah dibatalkan, dan pesanan di periode lain (harga pasarnya lain).
     *
     * Semuanya di satu transaksi: kalau satu pesanan gagal, tidak ada yang
     * berubah sama sekali.
     *
     * @param  array<int, numeric-string|float>  $prices  [order_item_id => harga]
     * @return array{lain: int, masihMenunggu: int} jumlah pesanan lain yang ikut diisi, dan berapa di antaranya yang masih menunggu harga barang lain
     */
    public function verifyOrder(Order $order, array $prices, bool $terapkanKeSemua = false): array
    {
        abort_if($order->dibatalkan(), 422, 'Pesanan ini sudah dibatalkan, harganya tidak perlu dikunci lagi.');

        // Harga tidak boleh diubah lagi begitu pembayarannya sudah berjalan.
        // Tanpa penjagaan ini, pengurus bisa mengubah harga pesanan yang sudah
        // lunas, dan catatannya jadi bohong: tertulis "Lunas Rp150.000"
        // padahal yang dibayar Rp100.000.
        //
        // Sengaja DITOLAK, bukan diperbaiki diam-diam. Selisih uang yang sudah
        // berpindah tidak bisa diselesaikan aplikasi; itu urusan pengurus dan
        // pemesan, entah dikembalikan atau ditambah. Aplikasinya cuma menahan
        // supaya angkanya tidak berubah tanpa ada yang tahu.
        abort_if(
            $order->payment_status !== PaymentStatus::Unpaid,
            422,
            'Pesanan ini pembayarannya sudah berjalan, harganya tidak bisa diubah lagi. Koordinasikan dulu dengan pemesannya.',
        );

        return DB::transaction(function () use ($order, $prices, $terapkanKeSemua) {
            // Baris pesanannya dikunci dan dibaca ulang. Kalau pemesan
            // membatalkan tepat bersamaan, salah satu menunggu yang lain, dan
            // pesanan yang sudah batal tidak "hidup lagi" jadi terverifikasi.
            $terkini = Order::whereKey($order->id)->lockForUpdate()->first();
            abort_if($terkini->dibatalkan(), 422, 'Pesanan ini baru saja dibatalkan, harganya tidak dikunci.');

            foreach ($prices as $orderItemId => $price) {
                $order->orderItems()
                    ->whereKey($orderItemId)
                    ->update(['price_at_order' => $price]);
            }

            $order->refresh();
            $total = $this->totalYangMuat($order);

            // Statusnya dikembalikan ke Verified, termasuk kalau tadinya sudah
            // Invoiced. Ini disengaja: invoice yang terlanjur dikirim memuat
            // harga lama, jadi harus dikirim ulang. Kalau statusnya dibiarkan
            // Invoiced, pengurus mengira pemesan sudah menerima angka yang benar.
            $order->update([
                'total_amount' => $total,
                'status' => OrderStatus::Verified,
            ]);

            if (! $terapkanKeSemua) {
                return ['lain' => 0, 'masihMenunggu' => 0];
            }

            // Harga per produk, diambil dari item yang BARUSAN diisi saja.
            $hargaPerProduk = $order->orderItems
                ->whereIn('id', array_keys($prices))
                ->mapWithKeys(fn ($item) => [$item->product_id => $item->price_at_order]);

            return $this->terapkanKePesananLain($order, $hargaPerProduk);
        });
    }

    /**
     * Berapa pesanan LAIN di periode yang sama yang juga masih menunggu harga
     * tiap barang fluktuatif di pesanan ini. Ditampilkan di samping pilihan
     * "terapkan ke semua", supaya pengurus tahu dampaknya sebelum menekan.
     *
     * Cakupannya harus sama persis dengan terapkanKePesananLain(), karena
     * angka ini janji tentang apa yang akan diubah.
     *
     * @return array<int, int> [product_id => jumlah pesanan lain]
     */
    public function pesananLainMenungguHarga(Order $order): array
    {
        $produkMenunggu = $order->orderItems->whereNull('price_at_order')->pluck('product_id')->unique();

        if ($produkMenunggu->isEmpty() || $order->dibatalkan()) {
            return [];
        }

        return OrderItem::query()
            ->whereIn('product_id', $produkMenunggu)
            ->whereNull('price_at_order')
            ->whereHas('order', fn ($query) => $this->cakupanPesananLain($query, $order))
            ->selectRaw('product_id, COUNT(DISTINCT order_id) AS jumlah')
            ->groupBy('product_id')
            ->pluck('jumlah', 'product_id')
            ->map(fn ($jumlah) => (int) $jumlah)
            ->all();
    }

    /**
     * Isi harga ke pesanan lain yang masih kosong harganya, lalu pesanan yang
     * jadi lengkap langsung dihitung totalnya dan ditandai terverifikasi.
     * Pesanan yang masih memuat barang fluktuatif lain tetap menunggu.
     *
     * @param  Collection<int, mixed>  $hargaPerProduk  [product_id => harga]
     * @return array{lain: int, masihMenunggu: int}
     */
    private function terapkanKePesananLain(Order $order, Collection $hargaPerProduk): array
    {
        $pesananLain = $this->cakupanPesananLain(Order::query(), $order)
            ->whereHas('orderItems', fn ($query) => $query
                ->whereIn('product_id', $hargaPerProduk->keys())
                ->whereNull('price_at_order'))
            ->get();

        $masihMenunggu = 0;

        foreach ($pesananLain as $lain) {
            foreach ($hargaPerProduk as $productId => $harga) {
                // whereNull: harga yang sudah diisi satu per satu tidak ditimpa
                $lain->orderItems()
                    ->where('product_id', $productId)
                    ->whereNull('price_at_order')
                    ->update(['price_at_order' => $harga]);
            }

            if (! $this->recalculateIfComplete($lain)) {
                $masihMenunggu++;
            }
        }

        return ['lain' => $pesananLain->count(), 'masihMenunggu' => $masihMenunggu];
    }

    /**
     * Pesanan mana yang boleh ikut diisi harganya lewat "terapkan ke semua":
     * periode yang sama, bukan pesanan yang sedang dibuka, belum dibatalkan,
     * dan belum dibayar. Dipakai bareng oleh penghitung dan pengisinya.
     */
    private function cakupanPesananLain(Builder $query, Order $order): Builder
    {
        return $query
            ->where('order_period_id', $order->order_period_id)
            ->whereKeyNot($order->id)
            ->belumDibatalkan()
            ->where('payment_status', PaymentStatus::Unpaid->value);
    }

    /**
     * Pemesan menyatakan sudah membayar lewat QRIS.
     *
     * Ini PERNYATAAN, bukan bukti uang sudah masuk. QRIS koperasi itu QRIS
     * statis, jadi tidak ada webhook yang memberi tahu aplikasi. Yang
     * menentukan lunas tetap pengurus setelah mencocokkan ke mutasi rekening
     * (lihat confirmPayment di bawah).
     *
     * Cuma boleh kalau totalnya sudah final. Pesanan yang masih memuat produk
     * fluktuatif belum punya angka yang bisa dibayar.
     */
    public function declarePaid(Order $order, ?UploadedFile $bukti = null): Order
    {
        abort_if($order->dibatalkan(), 422, 'Pesanan ini sudah dibatalkan, tidak perlu dibayar.');
        abort_if($order->total_amount === null, 422, 'Nominalnya belum final, pengurus belum mengunci harga.');
        abort_if($order->payment_status !== PaymentStatus::Unpaid, 422, 'Pembayaran pesanan ini sudah pernah dinyatakan.');

        $order->update([
            'payment_status' => PaymentStatus::AwaitingConfirmation,
            'paid_declared_at' => now(),
            // Bukti transfer opsional. Yang melampirkan memudahkan pengurus
            // mencocokkan; yang tidak, tetap dicek lewat mutasi.
            // Disk 'local', BUKAN 'public'. Bukti transfer memuat nama
            // pemilik rekening dan nomor rekening, jadi berkasnya tidak boleh
            // bisa dibuka siapa pun yang kebetulan punya URL-nya. Disajikan
            // lewat rute yang memeriksa izin dulu.
            'payment_proof_path' => $this->imageStorage->store($bukti, 'payment-proofs', 'local'),
        ]);

        return $order;
    }

    /**
     * Pengurus mencocokkan ke rekening lalu menyatakan lunas.
     *
     * Sengaja tidak menuntut pemesan menyatakan dulu: kadang orang membayar
     * tanpa menekan tombol apa pun, dan pengurus tetap harus bisa menandainya
     * setelah melihat uangnya masuk.
     */
    public function confirmPayment(Order $order): Order
    {
        abort_if($order->dibatalkan(), 422, 'Pesanan ini sudah dibatalkan, pembayarannya tidak bisa dikonfirmasi.');
        abort_if($order->total_amount === null, 422, 'Nominalnya belum final, pengurus belum mengunci harga.');

        // Aman ditekan dua kali (dua tab, tombol "kembali"): kalau sudah lunas,
        // jangan menimpa waktu konfirmasi yang pertama.
        if ($order->payment_status === PaymentStatus::Paid) {
            return $order;
        }

        $order->update([
            'payment_status' => PaymentStatus::Paid,
            'payment_confirmed_at' => now(),
        ]);

        return $order;
    }

    /**
     * Tandai invoice sudah dikirim. Ini konfirmasi manual dari admin setelah
     * dia menekan kirim di WhatsApp-nya sendiri; sistem tidak punya cara tahu
     * pesannya benar-benar terkirim karena cuma memakai tautan wa.me.
     *
     * Cuma pesanan terverifikasi yang boleh ditandai, karena pesanan pending
     * belum punya total yang pasti.
     */
    public function markAsInvoiced(Order $order): Order
    {
        abort_if($order->dibatalkan(), 422, 'Pesanan ini sudah dibatalkan, invoice-nya tidak perlu dikirim.');

        // Sudah ditandai sebelumnya (dua tab, tombol "kembali"): tidak ada yang
        // perlu diubah. Dulu kiriman kedua berakhir di halaman error berbunyi
        // "harus terverifikasi dulu", padahal pesanannya justru sudah terkirim.
        if ($order->status === OrderStatus::Invoiced) {
            return $order;
        }

        abort_unless($order->status === OrderStatus::Verified, 422, 'Pesanan harus terverifikasi dulu sebelum bisa ditandai invoice terkirim.');

        $order->update(['status' => OrderStatus::Invoiced]);

        return $order;
    }

    /**
     * Isi baris order_items. Dipakai bareng pesanan anggota & non-anggota
     * supaya logika penguncian harganya tidak ada dua salinan.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function createOrderItemsFor(Order $order, array $items): void
    {
        // Ambil semua produk yang dipesan sekaligus (1 query), biar tidak
        // query berulang di dalam loop.
        $products = Product::whereIn('id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('id');

        // Diurutkan per id produk supaya dua pesanan bersamaan yang isinya
        // sama selalu mengunci barisnya dengan urutan yang sama. Kalau
        // urutannya bisa berbeda, keduanya bisa saling menunggu.
        usort($items, fn (array $a, array $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            // Snapshot stok tercatat SEBELUM dikurangi, khusus produk yang
            // stoknya dilacak. Dari sini "melebihi stok" dihitung (quantity >
            // stok_saat_pesan) untuk ditinjau pengurus. Produk pre-order murni
            // stoknya null, jadi tidak pernah dianggap melebihi stok.
            $stokSaatPesan = $product->has_stock_tracking ? (int) $product->stock : null;

            // Stok dikurangi DULU, baru itemnya disimpan, dan urutan ini
            // penting. Menyimpan order_item membuat database mengambil kunci
            // baca pada baris produk karena ada relasi ke sana. Kalau
            // pengurangan stok (yang butuh kunci tulis) dilakukan sesudahnya,
            // dua pesanan bersamaan sama-sama memegang kunci baca lalu
            // sama-sama menunggu kunci tulis, dan keduanya macet.
            //
            // Cuma berlaku buat produk yang stoknya dilacak. Produk pre-order
            // murni tidak punya angka stok untuk dikurangi. Stok boleh jadi
            // minus (lihat kurangiStok) — pesanan tidak pernah ditolak karena
            // stok pada sistem pre-order.
            if ($product->has_stock_tracking) {
                $this->kurangiStok($product, $item['quantity']);
            }

            $order->orderItems()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'stok_saat_pesan' => $stokSaatPesan,
                // Produk non-fluktuatif: harga langsung dikunci pakai harga
                // jual saat ini. Produk fluktuatif (mis. telur, sayur):
                // dikosongkan dulu, baru diisi admin saat verifikasi.
                'price_at_order' => $product->is_fluctuating ? null : $product->sell_price,
            ]);
        }
    }

    /**
     * Kalau pesanan memuat barang yang melebihi stok tercatat, tandai butuh
     * ditinjau pengurus (17 Sep 2026). Pesanannya TIDAK ditolak — cuma diberi
     * penanda supaya muncul di dasbor dan pengurus memutuskan (Setujui: belanja
     * lebih / Tolak: sesuaikan ke stok). Lihat OrderCancellationService.
     */
    private function tandaiTinjauanStokBila(Order $order): void
    {
        $order->load('orderItems');

        if ($order->adaMelebihiStok()) {
            $order->update(['keputusan_stok' => KeputusanStok::Menunggu]);
        }
    }

    /**
     * Kurangi stok produk yang dilacak.
     *
     * Stok BOLEH menjadi minus, dan itu disengaja: pada sistem pre-order,
     * pesanan tidak pernah ditolak karena stok (keputusan Chandra, 16 Sep
     * 2026 — lihat CLAUDE.md). Angka minus justru berguna bagi pengurus
     * sebagai penanda "perlu belanja sebanyak itu lebih dari yang ada di
     * tangan". Karena tidak ada lagi syarat kecukupan stok, dua pesanan
     * bersamaan pun aman: decrement bersifat atomik di database, jadi
     * hasilnya tetap benar tanpa penjaga tambahan.
     */
    private function kurangiStok(Product $product, int $jumlah): void
    {
        Product::whereKey($product->id)->decrement('stock', $jumlah);
    }

    /**
     * Hitung ulang total dan status setelah isi pesanan berubah, mis. satu
     * barang dihapus pengurus (OrderCancellationService::hapusBarang()).
     *
     * Masih ada barang yang harganya kosong: kembali menunggu verifikasi,
     * tanpa total. Semua sudah berharga: terverifikasi dengan total baru.
     * Pesanan yang tadinya Invoiced ikut kembali ke Verified, karena invoice
     * lama memuat barang yang sudah tidak ada dan perlu dikirim ulang.
     */
    public function hitungUlang(Order $order): void
    {
        if (! $this->recalculateIfComplete($order)) {
            $order->update([
                'total_amount' => null,
                'status' => OrderStatus::Pending,
            ]);
        }
    }

    /**
     * Kalau semua item sudah punya harga, total langsung dihitung dan pesanan
     * ditandai terverifikasi tanpa perlu admin membukanya dulu.
     *
     * @return bool true kalau pesanannya sudah lengkap berharga
     */
    private function recalculateIfComplete(Order $order): bool
    {
        $order->load('orderItems');

        $adaYangBelumBerharga = $order->orderItems->contains(
            fn ($item) => $item->price_at_order === null
        );

        if ($adaYangBelumBerharga) {
            return false;
        }

        $total = $this->totalYangMuat($order);

        $order->update([
            'total_amount' => $total,
            'status' => OrderStatus::Verified,
        ]);

        return true;
    }

    /**
     * Total pesanan, dan pastikan muat di kolom total_amount (maksimal
     * Rp9.999.999.999,99). Harga & jumlah per barang sudah dibatasi di
     * validasi, tapi gabungan beberapa barang ekstrem masih bisa melewatinya;
     * lebih baik ditolak dengan pesan daripada berakhir di error database.
     */
    private function totalYangMuat(Order $order): float
    {
        $total = $order->orderItems->sum(fn ($item) => $item->quantity * $item->price_at_order);

        abort_if($total > 9_999_999_999.99, 422, 'Total pesanan terlalu besar untuk dicatat. Periksa lagi jumlah atau harganya.');

        return $total;
    }
}
