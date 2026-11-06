<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Services\Catalog\StockService;
use App\Services\Settings\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Katalog Cheery Chic: kategori, produk (foto, varian, stok awal, harga grosir, PO),
 * koleksi, dan foto hero contoh. Tanpa akun atau data transaksi, jadi aman untuk
 * server baru: php artisan db:seed --class=CatalogSeeder --force
 * Produk yang slug-nya sudah ada tidak diisi ulang foto/varian/stoknya.
 */
class CatalogSeeder extends Seeder
{
    public function __construct(private readonly StockService $stock) {}

    public function run(): void
    {
        // Foto hero contoh (model memakai Almira One Set); ganti lewat Admin › Tampilan Beranda.
        $settings = app(StoreSettings::class);
        if (! $settings->isStored('shop.storefront.hero_image')) {
            $settings->save(['shop.storefront.hero_image' => 'images/demo/hero-cheerychic.webp']);
        }

        $this->seedCatalog();
        $this->seedCollections();
    }

    private function seedCatalog(): void
    {
        // Kategori => [subkategori => [nama produk, ...]]. Kunci subkategori '' = produk langsung di kategori induk.
        $catalog = [
            'Daster & Dress' => [
                '' => ['Aluna Dress', 'Wilona Dress'],
            ],
            'One Set' => [
                'Setelan Celana Kulot' => ['Almira One Set', 'Nula One Set', 'Kalea One Set'],
            ],
        ];

        $descriptions = [
            'Daster & Dress' => 'Daster dan dress rayon premium yang adem, jatuh, dan ringan. Nyaman di rumah, tetap cantik dipakai keluar.',
            'One Set' => 'Setelan atasan dan celana kulot rayon premium motif ceria. Sekali pakai langsung rapi, praktis untuk di rumah maupun jalan santai.',
        ];

        $order = 0;
        foreach ($catalog as $categoryName => $groups) {
            $category = Category::updateOrCreate(['slug' => Str::slug($categoryName)], [
                'name' => $categoryName,
                'description' => $descriptions[$categoryName],
                'is_active' => true,
                'sort_order' => $order++,
            ]);

            foreach ($groups as $subName => $products) {
                $target = $subName === '' ? $category : Category::updateOrCreate(['slug' => Str::slug($subName)], [
                    'parent_id' => $category->id,
                    'name' => $subName,
                    'is_active' => true,
                ]);

                foreach ($products as $name) {
                    $this->seedProduct($target, $name, self::PRODUCTS[$name]);
                }
            }
        }
    }

    /** Koleksi contoh: satu produk bisa masuk beberapa koleksi (mis. Aluna di "Busui Friendly" dan "Motif Bunga"). */
    private function seedCollections(): void
    {
        $collections = [
            ['Rayon Premium', 'sparkles', null, 'Adem, lembut, dan jatuh. Bahan andalan semua koleksi Cheery Chic', ['daster-dress', 'one-set']],
            ['Motif Bunga', 'flower', null, 'Motif bunga ceria yang bikin hari di rumah makin semangat', ['aluna-dress', 'wilona-dress', 'kalea-one-set', 'nula-one-set']],
            ['Busui & Bumil Friendly', 'busui', null, 'Smoke melar & potongan lega, nyaman untuk hamil dan menyusui', ['aluna-dress', 'almira-one-set', 'nula-one-set']],
            ['Setelan Santai', 'set', null, 'Atasan + kulot sekali pakai langsung rapi', ['one-set']],
        ];

        foreach ($collections as $order => [$name, $icon, $tone, $tagline, $members]) {
            $collection = ProductCollection::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'tagline' => $tagline,
                'description' => "Koleksi {$name} pilihan Cheery Chic.",
                'icon' => $icon,
                'tone' => $tone,
                'is_active' => true,
                'show_on_home' => true,
                'sort_order' => $order,
            ]);

            // Slug kategori = semua produk di kategori itu (termasuk subkategori); selain itu slug produk.
            $ids = collect($members)->flatMap(function (string $slug) {
                $category = Category::where('slug', $slug)->first();

                return $category
                    ? Product::whereIn('category_id', $category->familyIds())->pluck('id')
                    : Product::where('slug', $slug)->pluck('id');
            })->unique()->all();

            $collection->products()->sync($ids);
        }
    }

    /** @param  array{price: int, compare: ?int, weight: int, featured: bool, sku: string, text: string, size: string, variants?: array{0: string, 1: list<array{0: string, 1: ?int}>}, preorder?: array{0: int, 1: ?string}, wholesale?: list<array{0: int, 1: int}>}  $data */
    private function seedProduct(Category $category, string $name, array $data): void
    {
        $product = Product::updateOrCreate(['slug' => Str::slug($name)], [
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $data['sku'],
            'description' => $data['text']."\n\nUkuran (cm):\n".$data['size']."\n\n• Bahan rayon premium: adem, lembut, menyerap keringat\n• Jahitan rapi, dicek satu per satu sebelum dikirim\n• Garansi tukar 7 hari bila ada cacat produksi\n\nWarna di layar bisa sedikit berbeda dengan aslinya karena pencahayaan foto.",
            'price' => $data['price'],
            'compare_at_price' => $data['compare'],
            'weight' => $data['weight'],
            'is_active' => true,
            'is_featured' => $data['featured'],
        ]);

        if (! $product->wasRecentlyCreated) {
            return;
        }

        // Contoh produk Pre Order (stok = kuota PO).
        if ($preorder = $data['preorder'] ?? null) {
            $product->update(['is_preorder' => true, 'preorder_days' => $preorder[0], 'preorder_note' => $preorder[1]]);
        }

        // Contoh harga grosir: [minimal pcs, harga per pcs].
        if ($wholesale = $data['wholesale'] ?? null) {
            $product->update(['wholesale_prices' => array_map(fn ($t) => ['min_quantity' => $t[0], 'price' => $t[1]], $wholesale)]);
        }

        // Foto contoh bawaan di public/images/demo/products (urutan = urutan varian motif);
        // ganti dengan unggahan sendiri lewat admin.
        $images = self::photos($product->slug, count($data['variants'][1] ?? [1]));
        $imageIds = [];
        foreach ($images as $position => $path) {
            $imageIds[] = $product->images()->create(['path' => $path, 'sort_order' => $position])->id;
        }

        if (isset($data['variants'])) {
            [$label, $variants] = $data['variants'];
            $product->update(['variant_label' => $label]);

            foreach ($variants as $position => [$variantName, $variantPrice]) {
                $price = $variantPrice ?? $data['price'];
                $variant = $product->variants()->create([
                    // Foto ke-n = motif ke-n, jadi memilih motif langsung menampilkan fotonya.
                    'product_image_id' => $imageIds[$position] ?? null,
                    'name' => $variantName,
                    'sku' => $product->sku.'-'.Str::upper(Str::slug($variantName, '')),
                    'price' => $price,
                    'compare_at_price' => $data['compare'] ? $data['compare'] + ($price - $data['price']) : null,
                    'is_active' => true,
                    'sort_order' => $position,
                ]);
                $this->stock->adjust($product, $variant, random_int(0, 6) === 0 ? 0 : random_int(5, 40), StockMovementType::Initial);
            }
        } else {
            $this->stock->adjust($product, null, random_int(0, 4) === 0 ? random_int(1, 4) : random_int(15, 90), StockMovementType::Initial);
        }
    }

    /** @return list<string> path foto contoh "{slug}-1.webp" s.d. "{slug}-{n}.webp" */
    private static function photos(string $slug, int $count): array
    {
        return array_map(fn (int $i) => "images/demo/products/{$slug}-{$i}.webp", range(1, $count));
    }

    /**
     * Produk contoh Cheery Chic (nama, ukuran, harga jual & harga coret dari etalase toko).
     * Foto: images/demo/products/{slug}-{n}.webp, urutannya sama dengan urutan varian motif.
     */
    private const PRODUCTS = [
        'Aluna Dress' => [
            'price' => 84000, 'compare' => 99000, 'weight' => 300, 'featured' => true, 'sku' => 'CC-ALUNA',
            'wholesale' => [[6, 79000], [12, 75000]],
            'text' => 'Aluna Dress dari rayon premium dengan smoke elastis di bagian dada dan rok lebar mayung yang mengembang cantik. Busui & bumil friendly, adem dipakai seharian, dan tetap manis untuk keluar rumah.',
            'size' => "Satu ukuran (all size)\n• Lingkar dada (LD): 120\n• Panjang baju (PB): 80\n• Lebar bawah: mayung",
            'variants' => ['Motif', [['Pelangi Pastel', null], ['Pink Blossom', null], ['Mawar Cream', null]]],
        ],
        'Wilona Dress' => [
            'price' => 61900, 'compare' => 120000, 'weight' => 300, 'featured' => true, 'sku' => 'CC-WILONA',
            'text' => 'Wilona Dress berbahan rayon premium dengan kerah V, lengan kerut manis, dan rok lebar mayung yang jatuh mewah. Daster rasa dress: nyaman di rumah, siap diajak pergi.',
            'size' => "All size\n• Lingkar dada (LD): 120\n• Panjang baju (PB): 100\n• Lebar bawah: mayung",
            'variants' => ['Motif', [['Mustard Bloom', null], ['Cream Floral', null], ['Pink Floral', null]]],
        ],
        'Almira One Set' => [
            'price' => 84900, 'compare' => 195000, 'weight' => 400, 'featured' => true, 'sku' => 'CC-ALMIRA',
            'wholesale' => [[6, 79900], [12, 75900]],
            'text' => 'Almira One Set: atasan tali dengan rempel di dada dan bagian belakang full smoke yang melar, dipadukan celana kulot panjang. Rayon premium yang adem, jatuh, dan tidak menerawang.',
            'size' => "All size\n• Lingkar dada (LD): 120 (belakang full smoke)\n• Panjang baju (PB): 50\n• Panjang celana (PC): 100\n• Lingkar pinggang (LP): 75 (karet)",
            'variants' => ['Motif', [['Mustard Leaf', null], ['Biru Denim', null], ['Hitam Floral', null], ['Batik Klasik', null]]],
        ],
        'Nula One Set' => [
            'price' => 94900, 'compare' => 195000, 'weight' => 400, 'featured' => true, 'sku' => 'CC-NULA',
            'text' => 'Nula One Set: atasan smoke kerah V dengan lengan rempel yang feminin, dipadu celana kulot panjang. Rayon premium yang lembut dan adem, nyaman dipakai seharian di rumah maupun jalan santai.',
            'size' => "All size\n• Atasan smoke elastis, nyaman untuk berbagai bentuk badan\n• Celana kulot panjang dengan pinggang karet",
            'variants' => ['Motif', [['Batik Cokelat', null], ['Salur Pastel', null], ['Terakota', null], ['Peach Floral', null]]],
        ],
        'Kalea One Set' => [
            'price' => 84900, 'compare' => 180000, 'weight' => 400, 'featured' => true, 'sku' => 'CC-KALEA',
            'preorder' => [14, 'Batch perdana, dijahit setelah pesanan masuk.'],
            'text' => 'Kalea One Set: atasan halter neck bertingkat dari rayon premium bermotif, dipadukan celana loose rayon twill polos yang jatuh. Ada saku di sisi kanan, praktis untuk aktivitas sehari-hari.',
            'size' => "All size\n• Lingkar dada (LD): 120\n• Panjang baju (PB): 60\n• Lingkar pinggang (LP): 75 (karet)\n• Panjang celana (PC): 95\n• Saku di sisi kanan",
            'variants' => ['Motif', [['Olive Floral', null], ['Peach Floral', null], ['Merah Ditsy', null]]],
        ],
    ];
}
