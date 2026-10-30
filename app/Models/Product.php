<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'description', 'price', 'compare_at_price', 'wholesale_prices',
    'weight', 'variant_label', 'is_active', 'is_featured',
    'is_preorder', 'preorder_days', 'preorder_note',
])]
class Product extends Model
{
    use HasFactory;

    /** Batas jumlah per baris keranjang untuk produk Pre Order (PO tidak dibatasi stok). */
    public const PREORDER_MAX_QTY = 99;

    /** Batas jumlah tingkat harga grosir per produk. */
    public const WHOLESALE_MAX_TIERS = 5;

    // `stock`, `has_variants`, `rating_avg`, `review_count` sengaja tidak mass-assignable. Stok diubah lewat
    // StockService (tercatat di riwayat), ringkasan varian lewat syncVariantSummary(),
    // ringkasan rating lewat ReviewService::syncSummary().

    /** Foto sampul selalu ikut dimuat karena dipakai di hampir semua tampilan produk. */
    protected $with = ['coverImage'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'wholesale_prices' => 'array',
            'stock' => 'integer',
            'weight' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_preorder' => 'boolean',
            'preorder_days' => 'integer',
            'has_variants' => 'boolean',
            'rating_avg' => 'float',
            'review_count' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Koleksi (bisa lebih dari satu), di luar kategori utama. */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'collection_product', 'product_id', 'collection_id');
    }

    #[Scope]
    protected function inCollection(Builder $query, ProductCollection|int $collection): void
    {
        $id = $collection instanceof ProductCollection ? $collection->id : $collection;
        $query->whereHas('collections', fn (Builder $q) => $q->whereKey($id));
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function preorder(Builder $query): void
    {
        $query->where('is_preorder', true);
    }

    /** Bisa dibeli sekarang: stok tersedia, atau produk Pre Order (tanpa batas stok). */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('stock', '>', 0)->orWhere('is_preorder', true));
    }

    /** Punya harga grosir (minimal satu tingkat). */
    #[Scope]
    protected function wholesale(Builder $query): void
    {
        // Disimpan null bila tidak ada tingkat (lihat ProductService), jadi cukup cek null.
        $query->whereNotNull('wholesale_prices');
    }

    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', "%{$term}%"));
        }));
    }

    public function imageUrl(): ?string
    {
        return $this->coverImage?->url();
    }

    /**
     * Samakan harga & stok produk dengan varian aktifnya: harga = varian termurah,
     * stok = total stok varian. Dipanggil setiap kali varian atau stoknya berubah.
     */
    public function syncVariantSummary(): void
    {
        $variants = $this->variants()->get();
        $active = $variants->where('is_active', true);

        $this->has_variants = $variants->isNotEmpty();

        if ($this->has_variants) {
            $this->stock = (int) $active->sum('stock');

            if ($cheapest = $active->sortBy('price')->first()) {
                $this->price = $cheapest->price;
                $this->compare_at_price = $cheapest->compare_at_price;
            }
        }

        $this->save();
        $this->unsetRelation('variants');
    }

    /** Ringkasan untuk pemilih produk di admin (Best Seller, Koleksi). */
    public function pickerPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'image' => $this->imageUrl(),
            'price' => $this->formattedPrice(),
            'stock' => $this->stock,
            'active' => $this->is_active,
        ];
    }

    public function formattedPrice(): string
    {
        return Money::rupiah($this->price);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->isOnSale()
            ? (int) round(100 - ($this->price / $this->compare_at_price * 100))
            : 0;
    }

    /** Bisa dibeli. Produk Pre Order selalu bisa dipesan tanpa melihat stok. */
    public function inStock(): bool
    {
        return $this->isPreorder() || $this->stock > 0;
    }

    /** Varian bisa dibeli: aktif dan (produk PO atau stok varian tersedia). */
    public function variantAvailable(ProductVariant $variant): bool
    {
        return $variant->is_active && ($this->isPreorder() || $variant->stock > 0);
    }

    /** Jumlah maksimal yang boleh dibeli per baris: stok, atau batas wajar untuk PO. */
    public function purchasableQuantity(?ProductVariant $variant = null): int
    {
        return $this->isPreorder() ? self::PREORDER_MAX_QTY : (int) ($variant?->stock ?? $this->stock);
    }

    /**
     * Produk Pre Order: bisa dipesan tanpa batas stok (stok tidak dikurangi),
     * lalu dikirim setelah masa produksi `preorder_days` hari sejak pembayaran.
     */
    public function isPreorder(): bool
    {
        return (bool) $this->is_preorder;
    }

    /** Lama proses PO dalam hari (minimal 1 bila PO aktif). */
    public function preorderDays(): int
    {
        return $this->isPreorder() ? max(1, (int) $this->preorder_days) : 0;
    }

    /** Contoh: "Dikirim ±14 hari setelah pembayaran". */
    public function preorderSummary(): ?string
    {
        return $this->isPreorder() ? self::preorderText($this->preorderDays()) : null;
    }

    public static function preorderText(int $days): string
    {
        return 'Dikirim ±'.$days.' hari setelah pembayaran';
    }

    /**
     * Tingkat harga grosir, urut dari jumlah terkecil. Berlaku untuk semua pembeli;
     * jumlah dihitung dari total semua varian produk ini di keranjang.
     *
     * @return list<array{min_quantity: int, price: int}>
     */
    public function wholesaleTiers(): array
    {
        return collect($this->wholesale_prices ?? [])
            ->filter(fn ($tier) => is_array($tier) && (int) ($tier['min_quantity'] ?? 0) > 1 && isset($tier['price']))
            ->map(fn ($tier) => ['min_quantity' => (int) $tier['min_quantity'], 'price' => (int) $tier['price']])
            ->sortBy('min_quantity')
            ->values()
            ->all();
    }

    public function hasWholesale(): bool
    {
        return $this->wholesaleTiers() !== [];
    }

    /** Jumlah minimal untuk mulai mendapat harga grosir. */
    public function wholesaleMinQuantity(): ?int
    {
        return $this->wholesaleTiers()[0]['min_quantity'] ?? null;
    }

    /** Harga grosir termurah (tingkat tertinggi), untuk label "Grosir mulai ...". */
    public function lowestWholesalePrice(): ?int
    {
        $tiers = $this->wholesaleTiers();

        return $tiers === [] ? null : min(array_column($tiers, 'price'));
    }

    /** Tingkat grosir yang berlaku untuk jumlah ini, atau null bila belum mencapai minimal. */
    public function wholesaleTierFor(int $quantity): ?array
    {
        return collect($this->wholesaleTiers())->last(fn ($tier) => $quantity >= $tier['min_quantity']);
    }

    /** Tingkat grosir berikutnya yang belum tercapai pada jumlah ini. */
    public function nextWholesaleTier(int $quantity): ?array
    {
        return collect($this->wholesaleTiers())->first(fn ($tier) => $quantity < $tier['min_quantity']);
    }

    /**
     * Harga satuan setelah grosir. Harga grosir tidak pernah menaikkan harga,
     * jadi varian yang lebih murah dari harga grosir tetap memakai harganya sendiri.
     */
    public function unitPriceFor(int $regularPrice, int $quantity): int
    {
        $tier = $this->wholesaleTierFor($quantity);

        return $tier ? min($regularPrice, $tier['price']) : $regularPrice;
    }

    public function hasReviews(): bool
    {
        return $this->review_count > 0;
    }

    /** Ringkasan singkat untuk meta description / Open Graph. */
    public function metaDescription(): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->description)));

        return Str::limit($text ?: $this->name.' original di '.config('shop.full_name').'. '.config('shop.tagline').'.', 155);
    }

    public function isLowStock(): bool
    {
        return ! $this->isPreorder() && $this->stock <= config('shop.low_stock_threshold');
    }
}
