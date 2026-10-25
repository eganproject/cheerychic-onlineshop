<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Data tampilan beranda (hero & bar pengumuman) dari config('shop.storefront'),
 * yang bisa diubah di Admin › Tampilan Beranda.
 */
class Storefront
{
    /** Ilustrasi bawaan bila belum ada foto hero. */
    public const DEFAULT_IMAGE = 'images/hero/hero-default.svg';

    public const DEFAULT_IMAGE_MOBILE = 'images/hero/hero-default-mobile.svg';

    /** Folder unggahan foto hero di disk public. */
    public const UPLOAD_DIR = 'storefront';

    /**
     * @return array{image: string, mobile_image: ?string, custom: bool, eyebrow: ?string, title: string, subtitle: ?string,
     *     primary: ?array{label: string, url: string}, secondary: ?array{label: string, url: string}, light: bool}
     */
    public static function hero(): array
    {
        $c = config('shop.storefront');
        $image = self::url($c['hero_image'] ?? null);
        $mobile = self::url($c['hero_image_mobile'] ?? null);

        return [
            'image' => $image ?? asset(self::DEFAULT_IMAGE),
            // Tanpa foto khusus ponsel: pakai foto utama (dipotong), atau ilustrasi versi ponsel.
            'mobile_image' => $mobile ?? ($image ? null : asset(self::DEFAULT_IMAGE_MOBILE)),
            'custom' => (bool) $image,
            'eyebrow' => $c['hero_eyebrow'] ?? null,
            'title' => $c['hero_title'] ?? config('shop.tagline'),
            'subtitle' => $c['hero_subtitle'] ?? null,
            'primary' => self::button($c['hero_primary_label'] ?? null, $c['hero_primary_url'] ?? null),
            'secondary' => self::button($c['hero_secondary_label'] ?? null, $c['hero_secondary_url'] ?? null),
            'light' => ($c['hero_text'] ?? 'light') !== 'dark',
        ];
    }

    /** Seksi beranda di bawah hero => label admin, dalam urutan bawaan. */
    public const SECTIONS = [
        'ribbon' => 'Pita slogan',
        'best_sellers' => 'Best Seller',
        'activities' => 'Belanja per koleksi',
        'banners' => 'Banner promo',
        'promo' => 'Panel diskon',
        'lookbook' => 'Lookbook motif',
        'why' => 'Kenapa Cheery Chic',
        'articles' => 'Artikel terbaru',
    ];

    /** Ikon tetap untuk poin "Kenapa Cheery Chic", sesuai urutan. */
    public const WHY_ICONS = ['sparkles', 'swatch', 'heart', 'truck'];

    /** @return list<string> seksi yang tampil, berurutan */
    public static function sections(): array
    {
        $sections = config('shop.storefront.sections');

        if (! is_array($sections)) {
            return array_keys(self::SECTIONS);
        }

        return array_values(array_unique(array_filter($sections, fn ($key) => is_string($key) && isset(self::SECTIONS[$key]))));
    }

    /** Teks seksi dari Admin › Tampilan Beranda; kosong = $fallback. */
    public static function text(string $key, ?string $fallback = null): ?string
    {
        $value = config('shop.storefront.'.$key);

        return filled($value) && is_string($value) ? $value : $fallback;
    }

    /** Kata di pita slogan (dipisah "•"). Bawaan: motto + tagar toko. */
    public static function ribbonWords(): array
    {
        $text = self::text('ribbon_text') ?? implode(' • ', array_filter([config('shop.motto'), config('shop.hashtag')]));

        return array_values(array_filter(array_map('trim', explode('•', $text)), 'filled'));
    }

    /** @return list<array{icon: string, title: string, text: ?string}> */
    public static function whyPoints(): array
    {
        return collect((array) config('shop.storefront.why_points', []))
            ->filter(fn ($line) => is_string($line) && filled($line))
            ->take(count(self::WHY_ICONS))->values()
            ->map(function (string $line, int $i) {
                [$title, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

                return ['icon' => self::WHY_ICONS[$i], 'title' => $title, 'text' => filled($text) ? $text : null];
            })->all();
    }

    /**
     * Judul dengan kata penekanan miring emas: tulis *kata* untuk menandai.
     * $lastWord: tanpa tanda bintang, kata terakhir yang ditekankan (dipakai judul hero).
     */
    public static function emphasize(?string $text, bool $lastWord = false): HtmlString
    {
        $html = e((string) $text);

        if (str_contains($html, '*')) {
            return new HtmlString(preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $html));
        }

        return new HtmlString($lastWord ? preg_replace('/(\S+?)([.!?…]*)$/u', '<em>$1</em>$2', $html, 1) : $html);
    }

    /** Teks tanpa tanda bintang penekanan (untuk atribut/alt). */
    public static function plain(?string $text): string
    {
        return str_replace('*', '', (string) $text);
    }

    /** @return list<string> */
    public static function announcements(): array
    {
        return array_values(array_filter((array) config('shop.storefront.announcements', []), 'filled'));
    }

    /** Path disk public ("storefront/x.jpg"), path folder public/ ("images/x.jpg"), atau URL. */
    public static function url(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return $value;
        }

        $value = ltrim($value, '/');

        return Str::startsWith($value, self::UPLOAD_DIR.'/')
            ? Storage::disk('public')->url($value)
            : asset($value);
    }

    /** Link internal ("/produk") jadi URL penuh; link luar dibiarkan. */
    public static function href(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /** @return ?array{label: string, url: string} */
    private static function button(?string $label, ?string $url): ?array
    {
        return filled($label) && filled($url) ? ['label' => $label, 'url' => self::href($url)] : null;
    }
}
