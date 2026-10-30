<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        return view('shop.cart', $this->pageData());
    }

    /**
     * Tambah ke keranjang. Dengan JS (Accept: application/json) dibalas JSON supaya
     * halaman tidak dimuat ulang; tanpa JS kembali ke halaman sebelumnya dengan toast.
     */
    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $variant = null;

        if ($product->has_variants) {
            $variant = $product->activeVariants()->find($data['variant_id'] ?? 0);

            if (! $variant) {
                return $this->failed($request, "Pilih {$this->variantLabel($product)} terlebih dahulu.", withInput: true);
            }
        }

        if (! ($variant ? $product->variantAvailable($variant) : $product->inStock())) {
            return $this->failed($request, 'Maaf, stok produk ini sedang habis.');
        }

        $requested = (int) ($data['quantity'] ?? 1);
        $key = CartService::key($product->id, $variant?->id);
        $before = $this->cart->find($key)?->quantity ?? 0;
        $inCart = $this->cart->add($product, $variant, $requested);
        $added = $inCart - $before;

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.index');
        }

        $name = $variant ? "{$product->name} ({$variant->name})" : $product->name;
        $stock = $product->purchasableQuantity($variant);

        [$type, $message] = match (true) {
            $product->isPreorder() && $added < $requested => ['warning', "Maksimal {$stock} pcs {$name} per pesanan Pre Order. Di keranjang: {$inCart} pcs."],
            $added < 1 => ['warning', "Stok {$name} tinggal {$stock} pcs dan semuanya sudah ada di keranjang."],
            $added < $requested => ['warning', "Hanya {$added} pcs {$name} yang bisa ditambahkan (stok tinggal {$stock}). Di keranjang: {$inCart} pcs."],
            $before > 0 => ['success', "{$added} pcs {$name} ditambahkan. Di keranjang sekarang {$inCart} pcs."],
            default => ['success', "{$added} pcs {$name} ditambahkan ke keranjang."],
        };

        if ($request->expectsJson()) {
            return response()->json([
                'type' => $type,
                'message' => $message,
                'added' => $added,
                'line_quantity' => $inCart,
                'count' => $this->cart->count(),
            ]);
        }

        return back()->with($type, $message);
    }

    /**
     * Ubah jumlah. Dengan JS (Accept: application/json) dibalas JSON berisi isi keranjang
     * yang sudah dirender ulang, supaya halaman tidak dimuat ulang.
     */
    public function update(Request $request, string $line): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:999']]);

        $cartLine = $this->cart->find($line);

        if (! $cartLine) {
            // Baris sudah hilang (mis. dihapus di tab lain): segarkan isi keranjang saja.
            abort_unless($request->expectsJson(), 404);

            return $this->cartResponse('warning', 'Produk itu sudah tidak ada di keranjang.');
        }

        $final = $this->cart->update($line, $data['quantity']);

        [$type, $message] = match (true) {
            $final < $data['quantity'] && $cartLine->isPreorder() => ['error', "Maksimal {$cartLine->availableStock()} pcs {$cartLine->name()} per pesanan Pre Order."],
            $final < $data['quantity'] => ['error', "Stok {$cartLine->name()} hanya tersisa {$cartLine->availableStock()}."],
            $final === 0 => ['success', "{$cartLine->name()} dihapus dari keranjang."],
            default => [null, null],
        };

        if ($request->expectsJson()) {
            return $this->cartResponse($type, $message);
        }

        return $type ? back()->with($type, $message) : back();
    }

    public function destroy(Request $request, string $line): RedirectResponse|JsonResponse
    {
        $cartLine = $this->cart->find($line);
        $this->cart->remove($line);

        $message = ($cartLine?->name() ?? 'Produk').' dihapus dari keranjang.';

        if ($request->expectsJson()) {
            return $this->cartResponse('success', $message);
        }

        return back()->with('success', $message);
    }

    /** Data halaman keranjang, dipakai halaman penuh maupun balasan JSON. */
    private function pageData(): array
    {
        return [
            'lines' => $this->cart->lines(),
            'adjustments' => $this->cart->adjustments(),
            'subtotal' => $this->cart->subtotal(),
            'weight' => $this->cart->totalWeight(),
            'preorderDays' => $this->cart->preorderDays(),
            'preorderMixed' => $this->cart->hasMixedPreorder(),
        ];
    }

    /** Balasan JSON untuk halaman keranjang: pesan toast, jumlah badge, dan isi keranjang terbaru. */
    private function cartResponse(?string $type, ?string $message): JsonResponse
    {
        return response()->json([
            'type' => $type,
            'message' => $message,
            'count' => $this->cart->count(),
            'html' => view('shop.cart.content', $this->pageData())->render(),
        ]);
    }

    private function failed(Request $request, string $message, bool $withInput = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['type' => 'error', 'message' => $message], 422);
        }

        $back = back()->with('error', $message);

        return $withInput ? $back->withInput() : $back;
    }

    private function variantLabel(Product $product): string
    {
        return mb_strtolower($product->variant_label ?: 'varian');
    }
}
