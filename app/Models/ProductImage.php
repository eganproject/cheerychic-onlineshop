<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'path', 'sort_order'])]
class ProductImage extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        // Hapus file fisik ketika record dihapus lewat Eloquent, setelah transaksi sukses.
        // Foto dari URL luar / aset bawaan folder public (data contoh) tidak punya file di disk.
        static::deleted(fn (ProductImage $image) => $image->isRemote() || $image->isBundled() ? null : DB::afterCommit(
            fn () => Storage::disk('public')->delete($image->path)
        ));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Path berupa URL lengkap (foto contoh dari CDN marketplace), bukan file di disk public. */
    public function isRemote(): bool
    {
        return str_starts_with((string) $this->path, 'https://') || str_starts_with((string) $this->path, 'http://');
    }

    /** Foto contoh bawaan aplikasi di folder public/images (mis. "images/demo/products/x.webp"). */
    public function isBundled(): bool
    {
        return str_starts_with((string) $this->path, 'images/');
    }

    public function url(): string
    {
        return match (true) {
            $this->isRemote() => $this->path,
            $this->isBundled() => asset($this->path),
            default => Storage::disk('public')->url($this->path),
        };
    }
}
