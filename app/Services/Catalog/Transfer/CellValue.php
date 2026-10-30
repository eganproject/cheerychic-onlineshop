<?php

namespace App\Services\Catalog\Transfer;

/**
 * Ubah isi sel menjadi tipe yang benar. Format yang lazim di Excel Indonesia
 * diterima: "Rp 150.000", "150.000", "1.250", "ya"/"tidak".
 * Nilai tidak valid melempar InvalidCell berisi pesan untuk admin.
 */
class CellValue
{
    public static function string(mixed $value, int $max, string $label): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        $value = trim((string) $value);

        if (mb_strlen($value) > $max) {
            throw new InvalidCell("{$label} maksimal {$max} karakter.");
        }

        return $value === '' ? null : $value;
    }

    public static function integer(mixed $value, string $label, int $min = 0, int $max = PHP_INT_MAX, bool $allowNegative = false): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            $number = $value;
        } elseif (is_float($value)) {
            if (floor($value) !== $value) {
                throw new InvalidCell("{$label} harus bilangan bulat.");
            }
            $number = (int) $value;
        } else {
            $text = preg_replace('/^rp\.?/i', '', str_replace([' ', "\u{00A0}"], '', (string) $value));
            $negative = $allowNegative && str_starts_with($text, '-');
            $text = ltrim($text, '+-');

            // Titik/koma sebagai pemisah ribuan (150.000 atau 150,000), atau ",00" di belakang.
            $text = preg_replace('/[.,]00$/', '', $text);
            if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $text)) {
                $text = str_replace(['.', ','], '', $text);
            }

            if (! preg_match('/^\d+$/', $text)) {
                throw new InvalidCell("{$label} harus berupa angka.");
            }
            $number = (int) $text * ($negative ? -1 : 1);
        }

        if ($number < $min || $number > $max) {
            throw new InvalidCell($max === PHP_INT_MAX
                ? "{$label} minimal ".number_format($min, 0, ',', '.').'.'
                : "{$label} harus antara ".number_format($min, 0, ',', '.').' dan '.number_format($max, 0, ',', '.').'.');
        }

        return $number;
    }

    /**
     * Harga grosir: "6=85000; 12=80000" (minimal pcs = harga per pcs, pisahkan dengan ; atau |).
     * Kosong = null (tidak berubah), "0" atau "-" = [] (hapus harga grosir).
     *
     * @return list<array{min_quantity: int, price: int}>|null
     */
    public static function wholesale(mixed $value, int $maxTiers): ?array
    {
        $text = trim((string) $value);

        if ($value === null || $text === '') {
            return null;
        }

        if ($text === '0' || $text === '-') {
            return [];
        }

        $tiers = [];
        foreach (preg_split('/\s*[;|\n]\s*/', $text, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if (! preg_match('/^(\d+)\s*(?:pcs)?\s*[=:]\s*(.+)$/i', $part, $m)) {
                throw new InvalidCell("Harga grosir \"{$part}\" tidak dikenali. Format: 6=85000; 12=80000.");
            }

            $min = (int) $m[1];
            if ($min < 2 || $min > 9999) {
                throw new InvalidCell('Minimal beli grosir harus antara 2 dan 9.999 pcs.');
            }
            if (isset($tiers[$min])) {
                throw new InvalidCell("Minimal beli grosir {$min} pcs ditulis dua kali.");
            }

            $tiers[$min] = self::integer($m[2], "Harga grosir {$min} pcs", 1, 1_000_000_000_000);
        }

        if (count($tiers) > $maxTiers) {
            throw new InvalidCell("Harga grosir maksimal {$maxTiers} tingkat.");
        }

        ksort($tiers);
        $previous = null;
        foreach ($tiers as $price) {
            if ($previous !== null && $price >= $previous) {
                throw new InvalidCell('Harga grosir harus makin murah untuk jumlah yang lebih banyak.');
            }
            $previous = $price;
        }

        return array_map(fn ($min, $price) => ['min_quantity' => $min, 'price' => $price], array_keys($tiers), $tiers);
    }

    public static function boolean(mixed $value, string $label): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return match (mb_strtolower(trim((string) $value))) {
            '1', 'ya', 'y', 'yes', 'true', 'aktif', 'iya' => true,
            '0', 'tidak', 't', 'n', 'no', 'false', 'nonaktif', 'tdk' => false,
            default => throw new InvalidCell("{$label} harus diisi \"ya\" atau \"tidak\"."),
        };
    }

    /** @return list<string> */
    public static function urls(mixed $value, int $max): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $urls = array_values(array_unique(array_filter(preg_split('/[\s|;]+/', trim((string) $value)))));

        if (count($urls) > $max) {
            throw new InvalidCell("Maksimal {$max} URL gambar per produk.");
        }

        foreach ($urls as $url) {
            if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                throw new InvalidCell("URL gambar tidak valid: {$url}");
            }
        }

        return $urls;
    }
}
