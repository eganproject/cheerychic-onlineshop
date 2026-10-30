<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'product_id', 'product_variant_id', 'product_name', 'variant_name', 'sku', 'price', 'retail_price', 'quantity', 'weight', 'subtotal', 'is_preorder', 'preorder_days'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'retail_price' => 'integer',
            'quantity' => 'integer',
            'weight' => 'integer',
            'subtotal' => 'integer',
            'is_preorder' => 'boolean',
            'preorder_days' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Baris ini mendapat harga grosir (harga lebih murah dari harga normal). */
    public function isWholesale(): bool
    {
        return $this->retail_price !== null && $this->retail_price > $this->price;
    }

    /** Total hemat dari harga grosir untuk baris ini. */
    public function wholesaleSavings(): int
    {
        return $this->isWholesale() ? ($this->retail_price - $this->price) * $this->quantity : 0;
    }

    public function isPreorder(): bool
    {
        return (bool) $this->is_preorder;
    }
}
