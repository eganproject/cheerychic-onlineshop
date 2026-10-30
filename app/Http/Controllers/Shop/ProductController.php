<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Services\Reviews\ReviewService;
use App\Services\Storefront\BestSellers;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public const SORTS = [
        'terbaru' => 'Terbaru',
        'termurah' => 'Harga terendah',
        'termahal' => 'Harga tertinggi',
        'nama' => 'Nama A-Z',
    ];

    public function index(Request $request): View
    {
        $category = $request->filled('kategori')
            ? Category::active()->with('parent')->where('slug', $request->string('kategori'))->first()
            : null;

        $collection = $request->filled('koleksi')
            ? ProductCollection::active()->where('slug', $request->string('koleksi'))->first()
            : null;

        $minPrice = $request->integer('harga_min') ?: null;
        $maxPrice = $request->integer('harga_max') ?: null;

        // Min & maks tertukar (mis. 500rb – 100rb): tukar supaya hasilnya tidak kosong.
        if ($minPrice && $maxPrice && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        $sort = array_key_exists($request->query('urut'), self::SORTS) ? $request->query('urut') : 'terbaru';

        $products = Product::active()
            ->with('category')
            ->search($request->string('q')->trim()->value() ?: null)
            ->when($category, fn ($q) => $q->whereIn('category_id', $category->familyIds()))
            ->when($collection, fn ($q) => $q->inCollection($collection))
            ->when($request->boolean('tersedia'), fn ($q) => $q->available())
            ->when($request->boolean('promo'), fn ($q) => $q->onSale())
            ->when($request->boolean('po'), fn ($q) => $q->preorder())
            ->when($minPrice, fn ($q) => $q->where('price', '>=', $minPrice))
            ->when($maxPrice, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->when($sort === 'terbaru', fn ($q) => $q->latest())
            ->when($sort === 'termurah', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'termahal', fn ($q) => $q->orderByDesc('price'))
            ->when($sort === 'nama', fn ($q) => $q->orderBy('name'))
            ->paginate(12)
            ->withQueryString();

        // Hitungan per kategori mengikuti koleksi yang sedang dibuka.
        $countable = fn ($q) => $q->active()->when($collection, fn ($q) => $q->inCollection($collection));

        $categories = Category::active()->root()
            ->withCount(['products' => $countable])
            ->with(['children' => fn ($q) => $q->active()->withCount(['products' => $countable])])
            ->orderBy('sort_order')->orderBy('name')->get()
            // Jumlah produk induk termasuk produk di subkategorinya.
            ->each(fn (Category $c) => $c->products_count += $c->children->sum('products_count'));

        if ($collection) {
            // Di halaman koleksi, sembunyikan kategori yang tidak punya produk di koleksi itu.
            $categories = $categories->filter(fn (Category $c) => $c->products_count > 0 || $category?->is($c) || (int) $category?->parent_id === $c->id)
                ->each(fn (Category $c) => $c->setRelation('children', $c->children->filter(fn (Category $child) => $child->products_count > 0 || $category?->is($child))->values()))
                ->values();
        }

        // Foto bulat di navigasi kategori: gambar kategori, atau foto produk terbaru di kategori itu.
        $categoryCovers = $categories->mapWithKeys(fn (Category $c) => [$c->id => $c->imageUrl()
            ?? Product::active()->whereIn('category_id', [$c->id, ...$c->children->pluck('id')])
                ->whereHas('coverImage')->latest()->first()?->imageUrl()]);

        return view('shop.products.index', [
            'products' => $products,
            'categories' => $categories,
            'categoryCovers' => $categoryCovers,
            'collections' => ProductCollection::active()->ordered()->get(['id', 'name', 'slug', 'icon']),
            'activeCollection' => $collection,
            'activeCategory' => $category,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            // Filter "Pre Order" hanya ditawarkan bila memang ada produk PO.
            'hasPreorder' => $request->boolean('po') || Product::active()->preorder()->exists(),
        ]);
    }

    /**
     * Halaman Best Seller: daftar berperingkat sesuai pengaturan admin (otomatis/manual),
     * lalu sisa katalog di bawahnya.
     */
    public function bestSellers(Request $request, BestSellers $bestSellers): View
    {
        $ranked = $bestSellers->forHome();

        return view('shop.products.best-sellers', [
            'ranked' => $request->integer('page', 1) > 1 ? collect() : $ranked,
            'manual' => BestSellers::isManual(),
            'others' => Product::active()
                ->with('category')
                ->whereNotIn('id', $ranked->pluck('id'))
                ->orderByDesc('is_featured')
                ->latest()
                ->paginate(12)
                ->withQueryString()
                ->fragment('lainnya'),
        ]);
    }

    public function show(Request $request, Product $product, ReviewService $reviewService): View
    {
        abort_unless($product->is_active, 404);

        $user = $request->user();

        return view('shop.products.show', [
            'product' => $product->load(['category.parent', 'images', 'activeVariants.image', 'collections' => fn ($q) => $q->active()->ordered()]),
            'related' => Product::active()
                ->where('category_id', $product->category_id)
                ->with('category')
                ->whereKeyNot($product->id)
                ->inRandomOrder()
                ->take(8)
                ->get(),
            'reviews' => $product->reviews()->approved()->with('user:id,name')
                ->latest('approved_at')->latest('id')
                ->paginate(5, pageName: 'ulasan')
                ->fragment('ulasan'),
            'ratingDistribution' => $product->review_count > 0 ? $reviewService->distribution($product) : [],
            'myReview' => $user?->isCustomer() ? $user->reviews()->where('product_id', $product->id)->first() : null,
            'canReview' => $user?->isCustomer() && $reviewService->canReview($user, $product),
        ]);
    }
}
