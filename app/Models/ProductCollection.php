<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Koleksi (Travel & Umrah, Premium Velvet, Hadiah Spesial, ...). Berbeda dari kategori (satu per produk),
 * satu produk bisa masuk banyak koleksi. Tampil sebagai ubin "Belanja per koleksi" di beranda
 * dan filter `?koleksi=slug` di katalog.
 */
#[Fillable(['name', 'slug', 'tagline', 'description', 'image_path', 'icon', 'tone', 'is_active', 'show_on_home', 'sort_order'])]
class ProductCollection extends Model
{
    use HasFactory;

    protected $table = 'collections';

    /**
     * Warna ubin di beranda => [kelas ubin, kelas ikon, label admin].
     * Palet Cheery Chic (krem, kakao, emas, blush, sage); kunci lama tetap dipakai
     * supaya pilihan yang sudah tersimpan tidak hilang.
     */
    public const TONES = [
        'sand' => ['bg-sand-100 text-ink', 'text-brand-500/25', 'Krem'],
        'ink' => ['bg-ink text-white', 'text-brand-300/40', 'Kakao'],
        'cyan' => ['bg-brand-300 text-ink', 'text-white/40', 'Emas'],
        'sky' => ['bg-blush-100 text-ink', 'text-blush-400/40', 'Blush'],
        'volt' => ['bg-sage-100 text-ink', 'text-sage-500/30', 'Sage'],
        'orange' => ['bg-navy-700 text-white', 'text-white/20', 'Kakao lembut'],
    ];

    /** Ikon garis yang tersedia di <x-category-icon> => label admin. */
    public const ICONS = [
        'dress' => 'Daster / dress',
        'set' => 'One set / setelan',
        'busui' => 'Busui & bumil',
        'kaftan' => 'Kaftan / homedress',
        'pajama' => 'Piyama',
        'jumbo' => 'Ukuran jumbo',
        'batik' => 'Batik / motif',
        'flower' => 'Motif bunga',
        'kids' => 'Anak / couple',
        'gift' => 'Hampers / hadiah',
        'accessories' => 'Aksesoris',
        'moon' => 'Ramadan / lebaran',
        'sparkles' => 'Kilau / premium',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_product', 'collection_id', 'product_id');
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Kunci ikon untuk <x-category-icon name>; null = ditebak dari nama. */
    public function iconName(): ?string
    {
        return array_key_exists((string) $this->icon, self::ICONS) ? $this->icon : null;
    }

    /** @return array{0: string, 1: string}|null kelas ubin & gelembung; null = warna bergiliran. */
    public function toneClasses(): ?array
    {
        $tone = self::TONES[(string) $this->tone] ?? null;

        return $tone ? [$tone[0], $tone[1]] : null;
    }

    public function url(): string
    {
        return route('products.index', ['koleksi' => $this->slug]);
    }
}
