<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Keranjang belanja berbasis session, jadi tamu pun bisa menambah produk
 * sebelum login. Isi session: [kunci => quantity], dengan kunci "produk"
 * atau "produk-varian" (lihat CartLine::key()).
 */
class CartService
{
    private const KEY = 'cart';

    /** Pesan perubahan isi keranjang (stok berubah/produk nonaktif) yang belum ditampilkan. */
    private const NOTICES_KEY = 'cart_notices';

    private ?Collection $lines = null;

    public function __construct(private readonly Session $session) {}

    public static function key(int $productId, ?int $variantId = null): string
    {
        return $variantId ? "{$productId}-{$variantId}" : (string) $productId;
    }

    /** @return array{0: int, 1: ?int} */
    public static function parseKey(string $key): array
    {
        [$productId, $variantId] = array_pad(explode('-', $key, 2), 2, null);

        return [(int) $productId, $variantId !== null ? (int) $variantId : null];
    }

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $raw = $this->raw();

        if ($raw === []) {
            return $this->lines = collect();
        }

        $parsed = collect($raw)->keys()->mapWithKeys(fn ($key) => [(string) $key => self::parseKey((string) $key)]);

        $products = Product::active()->whereKey($parsed->pluck(0)->unique()->all())->get()->keyBy('id');
        $variants = ProductVariant::with('image')->whereKey($parsed->pluck(1)->filter()->all())->get()->keyBy('id');

        // Buang item yang produk/variannya sudah tidak aktif/terhapus, dan sesuaikan dengan stok.
        $clean = [];
        $notices = [];
        $rows = [];

        foreach ($raw as $key => $qty) {
            [$productId, $variantId] = $parsed[(string) $key];
            $product = $products->get($productId);
            $variant = $variantId ? $variants->get($variantId) : null;

            if (! $this->purchasable($product, $variantId, $variant)) {
                $notices[] = $product
                    ? ($variant ? "{$product->name} ({$variant->name})" : $product->name).' dihapus dari keranjang karena stok habis atau tidak tersedia.'
                    : 'Satu produk dihapus dari keranjang karena sudah tidak dijual.';

                continue;
            }

            $variant?->setRelation('product', $product);
            $quantity = min($qty, $product->purchasableQuantity($variant));
            $rows[] = [$product, $variant, $quantity];
            $clean[self::key($product->id, $variant?->id)] = $quantity;

            if ($quantity < $qty) {
                $name = $variant ? "{$product->name} ({$variant->name})" : $product->name;
                $notices[] = "Jumlah {$name} disesuaikan menjadi {$quantity} karena stok tersisa {$product->purchasableQuantity($variant)}.";
            }
        }

        // Harga grosir dihitung dari total jumlah per produk (motif/varian boleh dicampur).
        $perProduct = self::quantitiesPerProduct(array_map(fn ($row) => [$row[0]->id, $row[2]], $rows));
        $lines = collect($rows)->map(fn ($row) => new CartLine($row[0], $row[2], $row[1], $perProduct[$row[0]->id]));

        if ($clean != $raw) {
            $this->session->put(self::KEY, $clean);
        }

        if ($notices !== []) {
            $this->session->put(self::NOTICES_KEY, [...(array) $this->session->get(self::NOTICES_KEY, []), ...$notices]);
        }

        return $this->lines = $lines;
    }

    /**
     * Total jumlah per produk, dasar harga grosir.
     *
     * @param  iterable<array{0: int, 1: int}>  $rows  [product_id, quantity]
     * @return array<int, int>
     */
    public static function quantitiesPerProduct(iterable $rows): array
    {
        $totals = [];

        foreach ($rows as [$productId, $quantity]) {
            $totals[$productId] = ($totals[$productId] ?? 0) + $quantity;
        }

        return $totals;
    }

    /** Tambah produk/varian. Mengembalikan jumlah akhir di keranjang. */
    public function add(Product $product, ?ProductVariant $variant = null, int $quantity = 1): int
    {
        $key = self::key($product->id, $variant?->id);
        $current = $this->raw()[$key] ?? 0;

        return $this->set($key, min($current + max(1, $quantity), $product->purchasableQuantity($variant)));
    }

    /** Ubah jumlah sebuah baris. Mengembalikan jumlah akhir (dibatasi stok). */
    public function update(string $key, int $quantity): int
    {
        $line = $this->find($key);

        if (! $line) {
            return 0;
        }

        return $this->set($key, min(max(0, $quantity), $line->availableStock()));
    }

    public function remove(string $key): void
    {
        $this->set($key, 0);
    }

    public function find(string $key): ?CartLine
    {
        return $this->lines()->first(fn (CartLine $line) => $line->key() === $key);
    }

    /**
     * Ambil (lalu hapus) pesan penyesuaian keranjang agar bisa ditampilkan sekali.
     *
     * @return list<string>
     */
    public function adjustments(): array
    {
        $this->lines();

        return array_values(array_unique((array) $this->session->pull(self::NOTICES_KEY, [])));
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, self::NOTICES_KEY]);
        $this->lines = null;
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function subtotal(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->subtotal());
    }

    /** Total hemat dari harga grosir di keranjang. */
    public function wholesaleSavings(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->wholesaleSavings());
    }

    /** Masa proses Pre Order terlama di keranjang (0 bila tidak ada produk PO). */
    public function preorderDays(): int
    {
        return (int) $this->lines()->max(fn (CartLine $line) => $line->preorderDays());
    }

    /** Keranjang berisi produk PO sekaligus produk ready stock. */
    public function hasMixedPreorder(): bool
    {
        $lines = $this->lines();

        return $lines->contains(fn (CartLine $line) => $line->isPreorder())
            && $lines->contains(fn (CartLine $line) => ! $line->isPreorder());
    }

    public function totalWeight(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->weight());
    }

    private function purchasable(?Product $product, ?int $variantId, ?ProductVariant $variant): bool
    {
        if (! $product) {
            return false;
        }

        if ($product->has_variants) {
            // Produk bervarian wajib punya varian aktif yang valid.
            return $variant && (int) $variant->product_id === $product->id && $product->variantAvailable($variant);
        }

        return $variantId === null && $product->inStock();
    }

    private function set(string $key, int $quantity): int
    {
        $raw = $this->raw();

        if ($quantity > 0) {
            $raw[$key] = $quantity;
        } else {
            unset($raw[$key]);
        }

        $this->session->put(self::KEY, $raw);
        $this->lines = null;

        return $quantity;
    }

    /** @return array<string, int> */
    private function raw(): array
    {
        return array_map('intval', (array) $this->session->get(self::KEY, []));
    }
}
