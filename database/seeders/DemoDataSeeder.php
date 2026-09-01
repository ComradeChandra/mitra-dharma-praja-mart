<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Data CONTOH anggota & produk, supaya aplikasi tidak terlihat kosong saat
 * didemokan dan modul rekap/grafik ada isinya.
 *
 * Semua data di sini fiktif. Begitu daftar asli dari koperasi datang
 * (sekitar 100 anggota dan 10 produk beserta harganya), data ini dihapus
 * lewat halaman admin lalu diganti data sungguhan. tidak perlu mengubah
 * seeder ini. Nama-nama anggota di bawah karangan; harga produk perkiraan
 * pasaran, bukan harga resmi koperasi.
 *
 * Aman dijalankan berkali-kali: pakai updateOrCreate, jadi tidak bikin
 * data dobel.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAnggota();
        $this->seedProduk();
    }

    /**
     * Anggota contoh. Format kode mengikuti contoh di CLAUDE.md ("0010 A"):
     * 4 digit + spasi + huruf. Password contoh semuanya "anggota123"
     * (di-hash otomatis lewat cast 'hashed' di model Member), di dunia
     * nyata dibuatkan admin satu per satu lewat form kelola anggota.
     */
    private function seedAnggota(): void
    {
        $anggota = [
            ['0001 A', 'Siti Nurhaliza', '628121000001', 'Jl. Kebon Kopi No. 12, Cimahi Tengah'],
            ['0002 A', 'Budi Santoso', '628121000002', 'Jl. Sangkuriang No. 45, Cimahi Utara'],
            ['0003 A', 'Dewi Lestari', '628121000003', 'Jl. Amir Machmud No. 88, Cimahi Tengah'],
            ['0004 A', 'Ahmad Fauzi', '628121000004', 'Jl. Gatot Subroto No. 7, Cimahi Selatan'],
            ['0005 A', 'Rina Marlina', '628121000005', 'Jl. Baros No. 21, Cimahi Tengah'],
            ['0006 A', 'Hendra Gunawan', '628121000006', 'Jl. Cibaligo No. 134, Cimahi Selatan'],
            ['0007 A', 'Yuli Rahmawati', '628121000007', 'Jl. Encep Kartawiria No. 9, Cimahi Utara'],
            ['0008 A', 'Dadan Hermawan', '628121000008', 'Jl. Kolonel Masturi No. 56, Cimahi Utara'],
            ['0009 A', 'Teti Suryani', '628121000009', 'Jl. Pesantren No. 30, Cimahi Tengah'],
            ['0010 A', 'Asep Saepudin', '628121000010', 'Jl. Melong Asih No. 18, Cimahi Selatan'],
            ['0011 A', 'Nia Kurniasih', '628121000011', 'Jl. Cihanjuang No. 77, Cimahi Utara'],
            ['0012 A', 'Rudi Hartono', '628121000012', 'Jl. Rancabentang No. 4, Cimahi Selatan'],
            ['0013 A', 'Lilis Suryani', '628121000013', 'Jl. Sriwijaya No. 15, Cimahi Tengah'],
            ['0014 A', 'Iwan Setiawan', '628121000014', 'Jl. Cimindi Raya No. 62, Cimahi Selatan'],
            ['0015 A', 'Wati Nurhayati', '628121000015', 'Jl. Padasuka No. 101, Cimahi Tengah'],
            ['0016 A', 'Agus Priyanto', '628121000016', 'Jl. Contong No. 23, Cimahi Utara'],
            ['0017 A', 'Sri Wahyuni', '628121000017', 'Jl. Leuwigajah No. 90, Cimahi Selatan'],
            ['0018 A', 'Bambang Irawan', '628121000018', 'Jl. Pasantren No. 5, Cimahi Tengah'],
            ['0019 A', 'Euis Komariah', '628121000019', 'Jl. Citeureup No. 41, Cimahi Utara'],
            ['0020 A', 'Deni Ramdani', '628121000020', 'Jl. Cibeureum No. 68, Cimahi Selatan'],
            ['0021 A', 'Nurul Hidayah', '628121000021', 'Jl. Sangkuriang No. 12, Cimahi Utara'],
            ['0022 A', 'Yayan Sopian', '628121000022', 'Jl. Baros No. 55, Cimahi Tengah'],
            // Dua anggota NONAKTIF, sengaja ada, biar terlihat bahwa status
            // aktif/nonaktif memang berpengaruh (mereka tidak muncul di
            // dropdown login & tidak dihitung di rekap "belum belanja").
            ['0023 A', 'Maman Suherman', '628121000023', 'Jl. Cimahi No. 3, Cimahi Tengah', false],
            ['0024 A', 'Ineu Rosmiati', '628121000024', 'Jl. Melong No. 27, Cimahi Selatan', false],
        ];

        foreach ($anggota as $baris) {
            [$kode, $nama, $wa, $alamat] = $baris;
            $aktif = $baris[4] ?? true;

            Member::updateOrCreate(
                ['member_code' => $kode],
                [
                    'full_name' => $nama,
                    'whatsapp_number' => $wa,
                    'address' => $alamat,
                    'password' => 'anggota123',
                    'is_active' => $aktif,
                ]
            );
        }
    }

    /**
     * Produk contoh, dikelompokkan per kategori seperti permintaan di rapat
     * ("perkategori... beras apa aja, gula apa aja").
     *
     * Sengaja bervariasi supaya semua kondisi tampilan kelihatan saat demo:
     * - ada yang harganya FLUKTUATIF (telur, cabai, bawang) → harga jual
     *   dikosongkan, diisi admin saat verifikasi;
     * - ada yang stoknya DILACAK dan ada yang tidak (pre-order murni);
     * - ada satu yang stoknya HABIS → tampil "Tidak tersedia" di katalog;
     * - ada satu yang NONAKTIF → tidak muncul sama sekali di katalog.
     */
    private function seedProduk(): void
    {
        // [kategori, nama, harga beli, harga jual, fluktuatif, lacak stok, stok, aktif]
        $produk = [
            ['Sembako', 'Beras Pandan Wangi 5kg', 65000, 72000, false, true, 40, true],
            ['Sembako', 'Beras Premium 10kg', 125000, 138000, false, true, 25, true],
            ['Sembako', 'Minyak Goreng 2L', 32000, 35000, false, true, 60, true],
            ['Sembako', 'Gula Pasir 1kg', 15000, 17000, false, true, 80, true],
            ['Sembako', 'Tepung Terigu 1kg', 11000, 13000, false, false, null, true],
            ['Sembako', 'Kecap Manis 600ml', 22000, 25000, false, false, null, true],

            ['Sayur & Segar', 'Telur Ayam 1kg', 28000, null, true, false, null, true],
            ['Sayur & Segar', 'Bawang Merah 1kg', 35000, null, true, false, null, true],
            ['Sayur & Segar', 'Cabai Merah 1kg', 45000, null, true, false, null, true],

            ['Kebersihan', 'Sabun Cuci Piring 800ml', 14000, 16500, false, true, 50, true],
            ['Kebersihan', 'Deterjen Bubuk 1,8kg', 38000, 42000, false, true, 30, true],
            ['Kebersihan', 'Pewangi Pakaian 900ml', 19000, 22000, false, false, null, true],
            // Stok HABIS, buat memperlihatkan status "Tidak tersedia" di katalog.
            ['Kebersihan', 'Pasta Gigi 190g', 16000, 18500, false, true, 0, true],

            ['Minuman', 'Kopi Bubuk 200g', 18000, 21000, false, true, 45, true],
            ['Minuman', 'Teh Celup isi 50', 9000, 11000, false, false, null, true],
            ['Minuman', 'Susu Kental Manis 490g', 12000, 14000, false, true, 35, true],
            ['Minuman', 'Air Mineral 1 Dus (48 gelas)', 22000, 26000, false, true, 20, true],

            ['Makanan Ringan', 'Mie Instan 1 Dus (40 pcs)', 108000, 118000, false, true, 15, true],
            ['Makanan Ringan', 'Biskuit Kaleng 650g', 45000, 52000, false, false, null, true],

            ['Alat Tulis', 'Buku Tulis 1 Lusin', 24000, 27000, false, false, null, true],
            ['Alat Tulis', 'Pulpen 1 Lusin', 18000, 21000, false, true, 40, true],

            // NONAKTIF, sengaja, biar terlihat produk nonaktif memang
            // disembunyikan dari katalog pelanggan.
            ['Sembako', 'Beras Merah 2kg (stok lama)', 30000, 34000, false, false, null, false],
        ];

        foreach ($produk as [$kategori, $nama, $beli, $jual, $fluktuatif, $lacakStok, $stok, $aktif]) {
            Product::updateOrCreate(
                ['name' => $nama],
                [
                    'category' => $kategori,
                    'buy_price' => $beli,
                    'sell_price' => $jual,
                    'is_fluctuating' => $fluktuatif,
                    'has_stock_tracking' => $lacakStok,
                    'stock' => $stok,
                    'is_active' => $aktif,
                ]
            );
        }
    }
}
