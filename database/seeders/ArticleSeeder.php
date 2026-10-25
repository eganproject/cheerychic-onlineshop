<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Artikel contoh (tanpa gambar sampul; unggah sendiri lewat Admin › Artikel). */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['tips-perawatan', 'Tips & Perawatan', 'Cara merawat daster dan bahan rayon supaya tetap adem, cerah, dan awet.'],
            ['panduan-belanja', 'Panduan Belanja', 'Bingung memilih? Panduan singkat memilih bahan, model, dan ukuran daster.'],
            ['kabar-cheery-chic', 'Kabar Cheery Chic', 'Promo, koleksi baru, dan info layanan Cheery Chic.'],
        ])->mapWithKeys(fn ($c, $i) => [$c[0] => ArticleCategory::updateOrCreate(['slug' => $c[0]], ['name' => $c[1], 'description' => $c[2], 'sort_order' => $i])]);

        $author = User::where('email', 'admin@cheerychic.test')->first();

        $articles = [
            [
                'slug' => 'cara-merawat-daster-rayon-agar-awet',
                'category' => 'tips-perawatan',
                'title' => 'Cara Merawat Daster Rayon agar Tetap Adem, Cerah, dan Awet',
                'excerpt' => 'Rayon itu adem dan lembut, tapi butuh sedikit perhatian saat dicuci. Ikuti langkah sederhana ini supaya daster favoritmu tetap cantik.',
                'tags' => ['perawatan', 'rayon', 'tips'],
                'products' => 'Daster & Dress',
                'days' => 2,
                'body' => '<p>Rayon jadi bahan favorit daster karena adem, ringan, dan jatuh di badan. Supaya warnanya tetap cerah dan seratnya tidak cepat rusak, ada beberapa kebiasaan kecil yang perlu diperhatikan.</p>'
                    .'<h2>1. Cuci pertama secara terpisah</h2><p>Motif rayon yang cerah kadang sedikit luntur di cucian pertama. Rendam sebentar dengan air dingin dan sedikit garam, lalu cuci terpisah.</p>'
                    .'<h2>2. Pakai air dingin & deterjen lembut</h2><p>Air panas membuat rayon menyusut. Kucek perlahan, jangan disikat, dan hindari putaran mesin yang kencang.</p>'
                    .'<h2>3. Jangan diperas terlalu kuat</h2><p>Cukup tekan-tekan untuk membuang air, lalu langsung dijemur supaya tidak kusut.</p>'
                    .'<h3>Yang sebaiknya dihindari</h3><ul><li>Pemutih berbahan klorin</li><li>Menjemur di bawah terik matahari langsung terlalu lama</li><li>Menyetrika dengan suhu tinggi</li></ul>'
                    .'<blockquote><p>Jemur dengan posisi terbalik di tempat teduh berangin. Warna motif tetap cerah, kain tetap lembut.</p></blockquote>'
                    .'<h2>4. Setrika suhu rendah</h2><p>Balik daster dan setrika dari sisi dalam dengan suhu rendah, atau cukup digantung dengan hanger setelah kering.</p>',
            ],
            [
                'slug' => 'panduan-memilih-daster-dan-one-set',
                'category' => 'panduan-belanja',
                'title' => 'Daster, Dress, atau One Set? Panduan Memilih Sesuai Kebutuhanmu',
                'excerpt' => 'Mau buat santai di rumah, menyusui, atau jalan sore? Kenali bedanya supaya pilihanmu pas dan nyaman dipakai seharian.',
                'tags' => ['panduan', 'ukuran', 'one set'],
                'products' => 'One Set',
                'days' => 6,
                'body' => '<p>Pakaian rumah yang nyaman bikin hari terasa lebih ringan. Tiga model ini paling banyak dicari, dan masing-masing punya kelebihan.</p>'
                    .'<h2>Pilih sesuai aktivitas</h2><ol><li><strong>Daster</strong>: paling praktis dan adem. Pilih model smoke di dada bila sedang hamil atau menyusui.</li><li><strong>Dress rok mayung</strong>: rasa daster tapi lebih rapi, cocok untuk jemput anak, arisan, atau belanja.</li><li><strong>One set</strong>: atasan dan kulot sekali pakai langsung serasi. Leluasa bergerak, tetap sopan.</li></ol>'
                    .'<h2>Cek ukuran</h2><p>Hampir semua koleksi Cheery Chic all size dengan lingkar dada (LD) 120 cm. Bandingkan dengan baju yang paling nyaman kamu pakai: ukur lebar dada lalu kalikan dua.</p>'
                    .'<h2>Bahan rayon premium</h2><p>Rayon menyerap keringat dan terasa dingin di kulit, pas untuk cuaca Indonesia yang panas.</p>',
            ],
            [
                'slug' => 'cheery-chic-kini-hadir-online',
                'category' => 'kabar-cheery-chic',
                'title' => 'Cheery Chic Kini Hadir Online: Belanja Daster Lebih Mudah',
                'excerpt' => 'Sekarang kamu bisa belanja daster dan one set Cheery Chic langsung dari website, lengkap dengan pembayaran aman dan lacak pesanan.',
                'tags' => ['info toko', 'promo'],
                'products' => null,
                'days' => 10,
                'body' => '<p>Toko online resmi Cheery Chic sudah bisa dipakai untuk berbelanja langsung dari rumah. <strong>Cantik di rumah, ceria setiap hari</strong> dengan daster rayon yang adem dan motif yang bikin semangat.</p>'
                    .'<h2>Yang bisa kamu lakukan</h2><ul><li>Bayar lewat transfer bank, e-wallet, QRIS, atau kartu kredit</li><li>Pilih kurir favorit dan lihat ongkir sebelum bayar</li><li>Lacak pesanan langsung dari halaman akun</li><li>Simpan motif incaran di wishlist</li></ul>'
                    .'<p>Pakai kode <strong>CERIA10</strong> untuk diskon 10% di pesanan pertamamu. Bagikan gaya rumahanmu dengan tagar <strong>#CeriaDiRumah</strong>.</p>',
            ],
        ];

        foreach ($articles as $data) {
            $article = Article::updateOrCreate(['slug' => $data['slug']], [
                'article_category_id' => $categories[$data['category']]->id,
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'body' => $data['body'],
                'tags' => $data['tags'],
                'status' => ArticleStatus::Published,
                'published_at' => now()->subDays($data['days'])->setTime(9, 0),
                'reading_minutes' => Article::readingMinutes($data['body']),
            ]);

            if ($author && ! $article->author_id) {
                $article->author()->associate($author)->save();
            }

            if ($data['products']) {
                $ids = Product::active()->whereHas('category', fn ($q) => $q->where('name', $data['products'])->orWhereHas('parent', fn ($p) => $p->where('name', $data['products'])))->take(4)->pluck('id');
                $article->products()->sync($ids->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
            }
        }
    }
}
