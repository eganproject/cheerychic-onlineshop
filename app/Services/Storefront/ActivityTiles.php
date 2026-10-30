<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Support\Collection;

/**
 * Isi ubin "Belanja per koleksi" & rel "Pilih koleksimu" di beranda.
 *
 * Sumber diatur di Admin › Tampilan Beranda (`shop.storefront.activities_source`):
 * "auto" = koleksi yang tampil di beranda, atau kategori induk bila belum ada koleksi;
 * "collections" = koleksi saja; "categories" = kategori induk saja.
 */
class ActivityTiles
{
    public const LIMIT = 8;

    public const SOURCES = [
        'auto' => 'Otomatis (koleksi, atau kategori bila belum ada koleksi)',
        'collections' => 'Koleksi',
        'categories' => 'Kategori induk',
    ];

    /**
     * @return array{kind: 'collections'|'categories', tiles: Collection<int, array{name: string, url: string, count: int,
     *     image: ?string, cover: ?string, icon: ?string, for: string, tone: ?array{0: string, 1: string}, tagline: ?string,
     *     chips: list<array{label: string, url: string}>}>}
     */
    public function forHome(): array
    {
        $this->usedCovers = [];
        $source = config('shop.storefront.activities_source', 'auto');

        if ($source !== 'categories') {
            $tiles = $this->collections();

            if ($tiles->isNotEmpty() || $source === 'collections') {
                return ['kind' => 'collections', 'tiles' => $tiles];
            }
        }

        return ['kind' => 'categories', 'tiles' => $this->categories()];
    }

    private function collections(): Collection
    {
        $collections = ProductCollection::active()->where('show_on_home', true)->ordered()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->take(self::LIMIT)->get();

        return $collections->values()->map(fn (ProductCollection $c, int $i) => [
            'name' => $c->name,
            'url' => $c->url(),
            'count' => (int) $c->products_count,
            'image' => $c->imageUrl(),
            'cover' => $c->image_path ? null : $this->cover(Product::active()->inCollection($c)),
            'icon' => $c->iconName(),
            'for' => $c->name.' '.$c->slug,
            'tone' => $c->toneClasses(),
            'tagline' => $c->tagline,
            // Ubin besar: jenis barang (kategori) yang ada di koleksi ini.
            'chips' => $i === 0 ? $this->collectionChips($c) : [],
        ]);
    }

    /** @return list<array{label: string, url: string}> */
    private function collectionChips(ProductCollection $collection): array
    {
        $categoryIds = Product::active()->inCollection($collection)->whereNotNull('category_id')
            ->toBase()->select('category_id')->selectRaw('count(*) as total')
            ->groupBy('category_id')->orderByDesc('total')->limit(4)->pluck('category_id');

        $categories = Category::active()->whereKey($categoryIds)->get(['id', 'name', 'slug'])->keyBy('id');

        return $categoryIds->map(fn ($id) => $categories->get($id))->filter()
            ->map(fn (Category $c) => [
                'label' => $c->name,
                'url' => route('products.index', ['koleksi' => $collection->slug, 'kategori' => $c->slug]),
            ])->values()->all();
    }

    private function categories(): Collection
    {
        return Category::active()->root()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->with(['children' => fn ($q) => $q->active()->withCount(['products' => fn ($q) => $q->active()])])
            ->orderBy('sort_order')->orderBy('name')->take(self::LIMIT)->get()
            ->values()
            ->map(fn (Category $c, int $i) => [
                'name' => $c->name,
                'url' => route('products.index', ['kategori' => $c->slug]),
                // Jumlah produk induk termasuk produk di subkategorinya.
                'count' => (int) $c->products_count + (int) $c->children->sum('products_count'),
                'image' => $c->imageUrl(),
                'cover' => $c->image_path ? null : $this->cover(Product::active()->whereIn('category_id', [$c->id, ...$c->children->pluck('id')])),
                'icon' => null,
                'for' => $c->name.' '.$c->slug,
                'tone' => null,
                'tagline' => null,
                'chips' => $i === 0 ? $c->children->take(4)->map(fn (Category $child) => [
                    'label' => $child->name,
                    'url' => route('products.index', ['kategori' => $child->slug]),
                ])->values()->all() : [],
            ]);
    }

    /** @var list<int> produk yang sudah jadi sampul ubin lain (supaya ubin tidak berfoto sama). */
    private array $usedCovers = [];

    /** Foto produk terbaru sebagai sampul ubin yang belum punya gambar sendiri; utamakan yang belum dipakai ubin lain. */
    private function cover(\Illuminate\Database\Eloquent\Builder $products): ?string
    {
        $products->whereHas('coverImage')->latest();
        $product = (clone $products)->whereKeyNot($this->usedCovers)->first() ?? $products->first();

        if (! $product) {
            return null;
        }

        $this->usedCovers[] = $product->id;

        return $product->imageUrl();
    }
}
