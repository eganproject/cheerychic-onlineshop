<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Halaman info toko (Tentang, FAQ, Kontak, Kebijakan Pengembalian). Isi ada di resources/views/shop/pages. */
class PageController extends Controller
{
    /** slug => [judul, deskripsi meta] */
    public const PAGES = [
        'tentang-kami' => ['Tentang Kami', 'Cerita Cheery Chic: daster, dress, dan one set rayon premium yang adem, LD 120 all size, dan ramah busui untuk perempuan Indonesia.'],
        'faq' => ['FAQ & Cara Pesan', 'Jawaban pertanyaan seputar ukuran, bahan rayon, cara pesan, pembayaran, pengiriman, pre order, dan harga grosir di Cheery Chic.'],
        'kontak' => ['Hubungi Kami', 'Hubungi tim Cheery Chic lewat WhatsApp, email, atau media sosial untuk tanya ukuran, motif, dan status pesanan.'],
        'kebijakan-pengembalian' => ['Kebijakan Tukar & Pengembalian', 'Syarat dan cara tukar barang di Cheery Chic: garansi tukar 7 hari sejak paket diterima untuk ukuran tidak pas atau barang cacat.'],
    ];

    public function __invoke(string $page): View
    {
        [$title, $description] = self::PAGES[$page];

        return view('shop.pages.show', [
            'page' => $page,
            'title' => $title,
            'description' => str_replace('7 hari', config('shop.policy.return_days').' hari', $description),
            'faq' => $page === 'faq' ? self::faq() : [],
        ]);
    }

    /** @return array<int, array{q: string, a: string}> */
    public static function faq(): array
    {
        $shop = config('shop.name');
        $days = config('shop.policy.return_days');
        $couriers = collect(config('shop.shipping.couriers'))->map(fn ($c) => strtoupper($c))->implode(', ');

        return [
            ['q' => 'Ukurannya seperti apa?', 'a' => 'Sebagian besar koleksi '.$shop.' berukuran all size dengan lingkar dada (LD) sampai 120 cm. Ukuran detail tiap model tercantum di deskripsi produk. Masih ragu? Chat kami, tim kami bantu cek.'],
            ['q' => 'Bahannya apa? Panas tidak?', 'a' => 'Kami memakai rayon premium yang lembut, adem, menyerap keringat, dan jatuh di badan, jadi nyaman dipakai seharian di cuaca panas.'],
            ['q' => 'Apakah bisa untuk ibu menyusui dan hamil?', 'a' => 'Banyak model kami busui dan bumil friendly, misalnya dengan kancing depan atau smoke elastis di dada. Lihat koleksi Busui & Bumil Friendly atau keterangan di halaman produk.'],
            ['q' => 'Bagaimana cara pesan?', 'a' => 'Pilih produk, tentukan motif dan jumlah, lalu klik Keranjang. Di halaman checkout isi alamat, pilih kurir, lalu bayar. Status pesanan bisa dipantau di menu Akun › Pesanan.'],
            ['q' => 'Pembayarannya lewat apa saja?', 'a' => 'Transfer bank (virtual account), e-wallet, QRIS, dan kartu kredit. Pembayaran diproses otomatis lewat payment gateway resmi, jadi pesanan langsung terkonfirmasi.'],
            ['q' => 'Dikirim pakai kurir apa dan berapa lama?', 'a' => 'Kami kirim ke seluruh Indonesia dengan kurir '.$couriers.'. Ongkir dan perkiraan waktu sampai muncul otomatis saat checkout sesuai alamatmu. Nomor resi bisa dilacak dari halaman pesanan.'],
            ['q' => 'Apa itu produk Pre Order (PO)?', 'a' => 'Produk berlabel PO dibuat atau disiapkan setelah kamu memesan. Lama prosesnya tertulis di halaman produk dan dihitung sejak pembayaran diterima.'],
            ['q' => 'Ada harga grosir?', 'a' => 'Ada. Produk berlabel Grosir otomatis lebih murah bila kamu membeli dalam jumlah tertentu (semua motif dalam satu produk dihitung bersama). Tabel harganya ada di halaman produk, tanpa perlu daftar reseller.'],
            ['q' => 'Kalau ukurannya tidak pas, bisa tukar?', 'a' => 'Bisa. Ada garansi tukar '.$days.' hari sejak paket diterima. Syarat lengkapnya ada di halaman Kebijakan Tukar & Pengembalian.'],
        ];
    }
}
