<?php

namespace App\Services\Catalog\Transfer;

use App\Models\Category;
use App\Models\Product;
use Generator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ekspor katalog & stok ke CSV/XLSX. Format ekspor produk sama persis dengan
 * template impor, jadi file hasil ekspor bisa diedit lalu diimpor ulang.
 */
class ProductExporter
{
    /** @return array<string, iterable<array>> */
    public function products(Builder $query): array
    {
        return [
            'Produk' => $this->productRows($query),
            'Petunjuk' => self::productGuide(),
        ];
    }

    /** @return array<string, iterable<array>> */
    public function stock(Builder $query): array
    {
        return [
            'Stok' => $this->stockRows($query),
            'Petunjuk' => self::stockGuide(),
        ];
    }

    /** @return array<string, iterable<array>> */
    public function productTemplate(): array
    {
        return [
            'Produk' => [
                ProductImporter::COLUMNS,
                ['', 'Kabel Data USB-C 1m', 'Kabel & Charger > Kabel Data', 'Kabel data fast charging 3A.', 'KBL-C1M', 'ya', 'tidak', '', '', '', '', 35000, 50000, 80, 25, 'https://contoh.com/foto/kabel-c.jpg', 0, '10=30000; 50=27500'],
                ['', 'Casing iPhone 15 Clear', 'Casing HP > Casing iPhone', 'Casing bening anti kuning.', '', 'ya', 'ya', 'Warna', 'Hitam', 'CS15-HTM', 'ya', 45000, '', 100, 10, '', 14, '6=40000; 12=38000'],
                ['', 'Casing iPhone 15 Clear', '', '', '', '', '', '', 'Putih', 'CS15-PTH', 'ya', 45000, '', 100, 8, '', '', ''],
            ],
            'Petunjuk' => self::productGuide(),
        ];
    }

    private function productRows(Builder $query): Generator
    {
        yield ProductImporter::COLUMNS;

        $categories = Category::pluck('name', 'id');
        $parents = Category::pluck('parent_id', 'id');
        $categoryPath = fn (?int $id) => $id
            ? ($parents[$id] ? $categories[$parents[$id]].' > ' : '').$categories[$id]
            : '';

        foreach ($query->without('coverImage')->with(['variants', 'images'])->orderBy('id')->lazy(200) as $product) {
            /** @var Product $product */
            $images = $product->images->map->url()->implode(' | ');
            $preorder = $product->isPreorder() ? $product->preorderDays() : 0;
            $wholesale = self::wholesaleCell($product);
            $base = [
                $product->slug,
                $product->name,
                $categoryPath($product->category_id),
                (string) $product->description,
                (string) $product->sku,
                $product->is_active ? 'ya' : 'tidak',
                $product->is_featured ? 'ya' : 'tidak',
                (string) $product->variant_label,
            ];

            if (! $product->has_variants) {
                yield [...$base, '', '', '', $product->price, $product->compare_at_price ?? '', $product->weight, $product->stock, $images, $preorder, $wholesale];

                continue;
            }

            foreach ($product->variants as $i => $variant) {
                yield [
                    ...$base,
                    $variant->name,
                    (string) $variant->sku,
                    $variant->is_active ? 'ya' : 'tidak',
                    $variant->price,
                    $variant->compare_at_price ?? '',
                    $variant->weight ?? '',
                    $variant->stock,
                    $i === 0 ? $images : '',
                    $i === 0 ? $preorder : '',
                    $i === 0 ? $wholesale : '',
                ];
            }
        }
    }

    private function stockRows(Builder $query): Generator
    {
        yield [...StockImporter::COLUMNS, 'kategori', 'aktif'];

        foreach ($query->without('coverImage')->with(['variants', 'category'])->orderBy('name')->lazy(200) as $product) {
            /** @var Product $product */
            if (! $product->has_variants) {
                yield [(string) $product->sku, $product->slug, $product->name, '', $product->stock, '', $product->category?->name ?? '', $product->is_active ? 'ya' : 'tidak'];

                continue;
            }

            foreach ($product->variants as $variant) {
                yield [(string) $variant->sku, $product->slug, $product->name, $variant->name, $variant->stock, '', $product->category?->name ?? '', $variant->is_active && $product->is_active ? 'ya' : 'tidak'];
            }
        }
    }

    /** Contoh: "6=85000; 12=80000", atau 0 bila tanpa harga grosir. */
    public static function wholesaleCell(Product $product): string|int
    {
        $tiers = $product->wholesaleTiers();

        return $tiers ? collect($tiers)->map(fn ($t) => $t['min_quantity'].'='.$t['price'])->join('; ') : 0;
    }

    public static function productGuide(): array
    {
        return [
            ['Kolom', 'Keterangan'],
            ['slug', 'Kosongkan untuk produk baru. Untuk mengubah produk yang sudah ada, isi slug-nya (lihat file ekspor). Produk juga dicocokkan lewat sku_produk atau nama produk.'],
            ['nama_produk', 'Wajib untuk produk baru. Produk bervarian: satu baris per varian, nama produk / slug sama.'],
            ['kategori', 'Nama kategori, atau "Induk > Subkategori". Kategori yang belum ada akan dibuat otomatis.'],
            ['deskripsi', 'Opsional.'],
            ['sku_produk', 'Opsional, harus unik.'],
            ['aktif / unggulan', '"ya" atau "tidak". Kosong = produk baru aktif & bukan unggulan; produk lama tidak berubah.'],
            ['label_varian', 'Judul pilihan varian, mis. "Warna" atau "Tipe HP".'],
            ['nama_varian', 'Isi hanya untuk produk bervarian, mis. "Hitam".'],
            ['sku_varian', 'Opsional, harus unik. Dipakai untuk mencocokkan varian yang sudah ada.'],
            ['varian_aktif', '"ya" atau "tidak".'],
            ['harga', 'Rupiah tanpa desimal, mis. 150000 atau 150.000. Wajib untuk produk/varian baru.'],
            ['harga_coret', 'Harga sebelum diskon (opsional). Isi 0 untuk menghapus harga coret.'],
            ['berat_gram', 'Berat dalam gram untuk hitung ongkir. Kosong = 100 gram untuk produk baru.'],
            ['stok', 'Stok saat ini. Perubahan stok tercatat di riwayat stok. Kosong = stok produk lama tidak berubah.'],
            ['url_gambar', 'Opsional. Satu atau beberapa URL foto (pisahkan dengan |). Hanya diunduh bila produk belum punya foto.'],
            ['pre_order_hari', 'Opsional. Isi lama proses Pre Order dalam hari (1–180) untuk menjadikan produk PO; produk PO bisa dipesan tanpa batas stok. Isi 0 = bukan PO (ready stock). Kosong = tidak berubah.'],
            ['harga_grosir', 'Opsional. Harga grosir bertingkat: minimal pcs = harga per pcs, pisahkan dengan titik koma, mis. "6=85000; 12=80000" (maks. '.Product::WHOLESALE_MAX_TIERS.' tingkat, makin banyak makin murah). Jumlah dihitung dari total semua varian produk. Isi 0 = hapus harga grosir. Kosong = tidak berubah.'],
            ['', ''],
            ['Catatan', 'Hanya sheet pertama yang dibaca. Kolom kosong pada produk lama tidak mengubah data. Produk yang salah satu barisnya error akan dilewati seluruhnya.'],
        ];
    }

    public static function stockGuide(): array
    {
        return [
            ['Kolom', 'Keterangan'],
            ['sku', 'SKU produk atau SKU varian. Bila kosong, produk dicocokkan lewat slug (+ nama_varian untuk produk bervarian).'],
            ['slug / nama_varian', 'Dipakai bila SKU kosong.'],
            ['nama_produk', 'Hanya informasi, tidak dibaca saat impor.'],
            ['stok', 'Mode "Ganti stok": stok menjadi angka ini. Mode "Tambah / kurangi": stok ditambah angka ini (pakai minus untuk mengurangi).'],
            ['catatan', 'Opsional, tampil di riwayat stok.'],
            ['kategori / aktif', 'Hanya informasi, tidak dibaca saat impor.'],
        ];
    }
}
