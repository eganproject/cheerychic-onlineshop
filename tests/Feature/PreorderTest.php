<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Orders\OrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreorderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Daster Kalea',
            'weight' => 300,
            'is_active' => 1,
            'price' => '85.000',
            'stock' => 30,
        ], $overrides);
    }

    public function test_admin_sets_and_clears_preorder_on_product(): void
    {
        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk()->assertSee('Pre Order')->assertSee('Lama proses');

        $this->post(route('admin.products.store'), $this->payload(['is_preorder' => 1]))
            ->assertSessionHasErrors('preorder_days');

        $this->post(route('admin.products.store'), $this->payload(['is_preorder' => 1, 'preorder_days' => 14, 'preorder_note' => 'Batch 2']))
            ->assertSessionHasNoErrors();

        $product = Product::firstWhere('name', 'Daster Kalea');
        $this->assertTrue($product->is_preorder);
        $this->assertSame(14, $product->preorder_days);
        $this->assertSame('Batch 2', $product->preorder_note);
        $this->assertSame(30, $product->stock);

        $this->get(route('admin.products.index', ['status' => 'pre-order']))->assertOk()->assertSee('Daster Kalea')->assertSee('PO 14 hari');

        // Mematikan PO menghapus detailnya.
        $this->put(route('admin.products.update', $product), $this->payload(['is_preorder' => 0, 'preorder_days' => 14, 'stock_original' => 30]))
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertFalse($product->is_preorder);
        $this->assertNull($product->preorder_days);
        $this->assertNull($product->preorder_note);
    }

    public function test_storefront_labels_and_filters_preorder_products(): void
    {
        $po = Product::factory()->create(['name' => 'Daster Pesanan', 'is_preorder' => true, 'preorder_days' => 10, 'preorder_note' => 'Dikirim mulai 20 November']);
        Product::factory()->create(['name' => 'Daster Ready']);

        $this->get(route('products.index'))->assertOk()->assertSee('Pre Order')->assertSee('PO ±10 hari');
        $this->get(route('products.index', ['po' => 1]))->assertOk()->assertSee('Daster Pesanan')->assertDontSee('Daster Ready');

        $this->get(route('products.show', $po))->assertOk()
            ->assertSee('Dikirim ±10 hari setelah pembayaran')
            ->assertSee('Dikirim mulai 20 November')
            ->assertSee('Pre Order sekarang')
            ->assertSee('https://schema.org/PreOrder', false);
    }

    public function test_preorder_is_not_limited_by_stock(): void
    {
        $po = Product::factory()->create(['name' => 'Daster Pesanan', 'is_preorder' => true, 'preorder_days' => 7, 'stock' => 0]);
        $variantPo = Product::factory()->create(['is_preorder' => true, 'preorder_days' => 7]);
        $motif = ProductVariant::factory()->for($variantPo)->create(['name' => 'Motif A', 'stock' => 0]);
        $variantPo->syncVariantSummary();

        $this->get(route('products.index', ['tersedia' => 1]))->assertOk()->assertSee('Daster Pesanan');
        $this->get(route('products.show', $po))->assertOk()->assertSee('Pre Order sekarang')->assertDontSee('Stok habis');

        $this->post(route('cart.store', $po), ['quantity' => 30])->assertSessionHas('success');
        $this->post(route('cart.store', $variantPo), ['variant_id' => $motif->id, 'quantity' => 2])->assertSessionHas('success');
        $this->assertSame([(string) $po->id => 30, "{$variantPo->id}-{$motif->id}" => 2], session('cart'));

        // Tetap ada batas wajar per pesanan.
        $this->post(route('cart.store', $po), ['quantity' => 500])->assertSessionHas('warning');
        $this->assertSame(Product::PREORDER_MAX_QTY, session('cart')[(string) $po->id]);
    }

    public function test_checkout_records_preorder_items_and_orders_show_estimate(): void
    {
        $customer = User::factory()->create();
        $address = Address::factory()->for($customer)->create();
        $po = Product::factory()->create(['name' => 'Daster Pesanan', 'is_preorder' => true, 'preorder_days' => 14, 'stock' => 0]);
        $ready = Product::factory()->create(['name' => 'Daster Ready', 'stock' => 5]);

        $this->actingAs($customer);
        $this->post(route('cart.store', $po), ['quantity' => 8]);
        $this->post(route('cart.store', $ready), ['quantity' => 1]);

        $this->get(route('cart.index'))->assertOk()->assertSee('Pre Order · ±14 hari')->assertSee('Ada produk Pre Order')
            ->assertSee('Produk ready stock dikirim bersamaan');
        $this->get(route('checkout.index'))->assertOk()->assertSee('Ada produk Pre Order');

        $this->post(route('checkout.store'), ['address_id' => $address->id, 'shipping' => 'jne|REG'])->assertRedirect();

        $order = Order::with('items')->sole();
        $poItem = $order->items->firstWhere('product_id', $po->id);
        $this->assertTrue($poItem->is_preorder);
        $this->assertSame(14, $poItem->preorder_days);
        $this->assertFalse($order->items->firstWhere('product_id', $ready->id)->is_preorder);
        $this->assertSame(0, $po->refresh()->stock, 'Stok produk PO tidak dikurangi.');
        $this->assertSame(4, $ready->refresh()->stock);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $po->id]);

        // Mengubah produk tidak mengubah pesanan yang sudah ada.
        $po->update(['is_preorder' => false, 'preorder_days' => null]);
        $this->assertTrue($order->refresh()->hasPreorder());

        $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now()->setDate(2026, 11, 1)])->save();
        $this->assertSame('2026-11-15', $order->preorderReadyAt()->toDateString());

        $this->get(route('account.orders.show', $order))->assertOk()->assertSee('Ada produk Pre Order')->assertSee('15 November 2026');
        $this->get(route('account.orders.index'))->assertOk()->assertSee('Pre Order');

        $this->actingAs($this->admin);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Pesanan berisi produk Pre Order')->assertSee('15 November 2026');
        $this->get(route('admin.orders.index', ['jenis' => 'pre-order']))->assertOk()->assertSee($order->code)->assertSee('siap 15 Nov');
        $this->get(route('admin.orders.index', ['jenis' => 'ready']))->assertOk()->assertDontSee($order->code);
        $this->get(route('admin.orders.invoice', $order))->assertOk()->assertSee('Pre Order ±14 hari');

        // Batal: hanya stok produk ready yang dikembalikan.
        app(OrderService::class)->cancel($order);
        $this->assertSame(0, $po->refresh()->stock);
        $this->assertSame(5, $ready->refresh()->stock);
    }
}
