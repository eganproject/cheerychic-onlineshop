<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductVariant;

final readonly class CartLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
        public ?ProductVariant $variant = null,
        // Total jumlah produk ini di keranjang (semua varian), dasar hitungan harga grosir.
        public ?int $productQuantity = null,
    ) {}

    /** Kunci baris di session: "12" untuk produk biasa, "12-5" untuk varian. */
    public function key(): string
    {
        return CartService::key($this->product->id, $this->variant?->id);
    }

    /** Nama untuk ditampilkan, contoh: "Casing iPhone 15 (Hitam)". */
    public function name(): string
    {
        return $this->variant ? "{$this->product->name} ({$this->variant->name})" : $this->product->name;
    }

    public function isPreorder(): bool
    {
        return $this->product->isPreorder();
    }

    public function preorderDays(): int
    {
        return $this->product->preorderDays();
    }

    public function isLowStock(): bool
    {
        return ! $this->isPreorder() && $this->availableStock() <= config('shop.low_stock_threshold');
    }

    /** Harga satuan setelah grosir (bila jumlah produk ini mencapai tingkat grosir). */
    public function unitPrice(): int
    {
        return $this->product->unitPriceFor($this->regularPrice(), $this->wholesaleQuantity());
    }

    /** Harga satuan normal (eceran) produk/varian. */
    public function regularPrice(): int
    {
        return $this->variant?->price ?? $this->product->price;
    }

    /** Harga coret: harga normal saat dapat grosir, atau harga coret produk. */
    public function compareAtPrice(): ?int
    {
        if ($this->isWholesale()) {
            return $this->regularPrice();
        }

        return $this->variant ? $this->variant->compare_at_price : $this->product->compare_at_price;
    }

    public function wholesaleQuantity(): int
    {
        return $this->productQuantity ?? $this->quantity;
    }

    public function isWholesale(): bool
    {
        return $this->unitPrice() < $this->regularPrice();
    }

    /** Hemat dari harga grosir pada baris ini. */
    public function wholesaleSavings(): int
    {
        return ($this->regularPrice() - $this->unitPrice()) * $this->quantity;
    }

    /** Tingkat grosir berikutnya: ['min_quantity', 'price', 'missing' => kurang berapa pcs]. */
    public function nextWholesaleTier(): ?array
    {
        $tier = $this->product->nextWholesaleTier($this->wholesaleQuantity());

        if (! $tier || $tier['price'] >= $this->unitPrice()) {
            return null;
        }

        return [...$tier, 'missing' => $tier['min_quantity'] - $this->wholesaleQuantity()];
    }

    public function unitWeight(): int
    {
        return $this->variant?->weight ?: $this->product->weight;
    }

    /** Jumlah maksimal di keranjang: stok, atau batas per baris untuk Pre Order. */
    public function availableStock(): int
    {
        return $this->product->purchasableQuantity($this->variant);
    }

    public function sku(): ?string
    {
        return $this->variant?->sku ?: $this->product->sku;
    }

    public function imageUrl(): ?string
    {
        return $this->variant?->image?->url() ?? $this->product->imageUrl();
    }

    public function subtotal(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function weight(): int
    {
        return $this->unitWeight() * $this->quantity;
    }
}
