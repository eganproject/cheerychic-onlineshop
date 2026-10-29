<?php

namespace Database\Seeders;

use App\Enums\ReviewStatus;
use App\Enums\StaffRole;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Enums\VoucherType;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Catalog\StockService;
use App\Services\Reviews\ReviewService;
use App\Services\Settings\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function __construct(private readonly StockService $stock) {}

    public function run(): void
    {
        // Data contoh (akun berpassword "password", pesanan palsu) tidak boleh masuk server produksi.
        if (app()->isProduction()) {
            $this->command?->error('Seeder contoh tidak dijalankan di produksi. Buat akun Superadmin dengan: php artisan shop:create-admin');

            return;
        }

        User::updateOrCreate(['email' => 'admin@cheerychic.test'], [
            'name' => 'Admin Cheery Chic',
            'password' => 'password',
            'phone' => '081200000000',
            'role' => UserRole::Admin,
            'staff_role' => StaffRole::Owner,
            'email_verified_at' => now(),
        ]);

        // Contoh staf dengan akses terbatas.
        foreach ([
            ['gudang@cheerychic.test', 'Rudi Hartono', StaffRole::Warehouse],
            ['cs@cheerychic.test', 'Sari Wulandari', StaffRole::CustomerService],
        ] as [$email, $name, $staffRole]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => UserRole::Admin,
                'staff_role' => $staffRole,
                'email_verified_at' => now(),
            ]);
        }

        $customer = User::updateOrCreate(['email' => 'pelanggan@cheerychic.test'], [
            'name' => 'Aisyah Putri',
            'password' => 'password',
            'phone' => '081311112222',
            'role' => UserRole::Customer,
            'email_verified_at' => now(),
        ]);

        if (! $customer->addresses()->exists()) {
            $customer->addresses()->create([
                'label' => 'Rumah',
                'recipient_name' => 'Aisyah Putri',
                'phone' => '081311112222',
                'province_id' => '2',
                'province' => 'Jawa Barat',
                'city_id' => '201',
                'city' => 'Kota Bandung',
                'district_id' => '20101',
                'district' => 'Coblong',
                'postal_code' => '40132',
                'address_line' => 'Jl. Dago No. 10, RT 02/RW 05',
                'is_default' => true,
            ]);
        }

        // Foto hero contoh (model memakai Almira One Set); ganti lewat Admin › Tampilan Beranda.
        $settings = app(StoreSettings::class);
        if (! $settings->isStored('shop.storefront.hero_image')) {
            $settings->save(['shop.storefront.hero_image' => 'images/demo/hero-cheerychic.webp']);
        }

        $this->seedCatalog();
        $this->seedCollections();
        // Tanpa event model supaya ratusan pesanan contoh tidak mengantrekan email.
        Order::withoutEvents(fn () => $this->call(DemoOrderSeeder::class));
        $this->seedReviews();
        $this->seedVouchers();
        $this->call(ArticleSeeder::class);
    }

    /** Voucher contoh (banner perlu gambar, jadi diunggah sendiri lewat admin). */
    private function seedVouchers(): void
    {
        $vouchers = [
            ['code' => 'CERIA10', 'name' => 'Diskon 10% pembelian pertama', 'type' => VoucherType::Percent, 'value' => 10, 'max_discount' => 25000, 'min_spend' => 80000, 'per_user_limit' => 1],
            ['code' => 'ONGKIRGRATIS', 'name' => 'Gratis ongkir s.d. Rp20.000', 'type' => VoucherType::FreeShipping, 'value' => 20000, 'min_spend' => 150000, 'per_user_limit' => 3],
            ['code' => 'CHEERY25K', 'name' => 'Potongan Rp25.000 belanja 2 set', 'type' => VoucherType::Fixed, 'value' => 25000, 'min_spend' => 160000, 'quota' => 100, 'per_user_limit' => 1],
        ];

        foreach ($vouchers as $voucher) {
            Voucher::updateOrCreate(['code' => $voucher['code']], $voucher + ['is_active' => true, 'is_public' => true]);
        }
    }

    /** Contoh ulasan yang sudah tayang supaya tampilan rating terlihat saat demo. */
    private function seedReviews(): void
    {
        $samples = [
            [5, 'Adem banget, motifnya cantik', 'Rayonnya lembut dan adem, dipakai seharian di rumah tidak gerah. Motifnya lebih cantik dari foto, jahitan rapi. Bakal repeat order warna lain!'],
            [4, 'Pas di badan, warna sesuai', 'LD 120 jadi lega untuk badan saya yang BB 65 kg. Warna sesuai foto, cuma pengiriman agak lama karena kurir. Admin ramah dan fast respon.'],
            [5, 'Bisa dipakai keluar rumah juga', 'Awalnya beli buat daster, ternyata cakep dipakai ke warung dan jemput anak. Smoke-nya melar nyaman, busui friendly. Recommended!'],
        ];

        $reviewers = collect(['Rina Wulandari', 'Nur Aisyah', 'Dewi Lestari'])->map(fn ($name) => User::firstOrCreate(
            ['email' => Str::slug($name, '.').'@contoh.test'],
            ['name' => $name, 'password' => 'password', 'email_verified_at' => now()],
        ));

        $reviewService = app(ReviewService::class);

        Product::where('is_featured', true)->get()->each(function (Product $product) use ($reviewers, $samples, $reviewService) {
            foreach ($reviewers as $i => $reviewer) {
                [$rating, $title, $body] = $samples[($product->id + $i) % count($samples)];

                ProductReview::firstOrNew(['user_id' => $reviewer->id, 'product_id' => $product->id])->forceFill([
                    'user_id' => $reviewer->id,
                    'product_id' => $product->id,
                    'rating' => $rating,
                    'title' => $title,
                    'body' => $body,
                    'status' => ReviewStatus::Approved,
                    'approved_at' => now()->subDays($i * 3),
                ])->save();
            }

            $reviewService->syncSummary($product);
        });
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

    /** @param  array{price: int, compare: ?int, weight: int, featured: bool, sku: string, text: string, size: string, variants?: array{0: string, 1: list<array{0: string, 1: ?int}>}, preorder?: array{0: int, 1: ?string}}  $data */
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
