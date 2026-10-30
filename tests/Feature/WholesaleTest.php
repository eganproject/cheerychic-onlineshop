<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\Transfer\CellValue;
use App\Services\Catalog\Transfer\InvalidCell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WholesaleTest extends TestCase
{
    use RefreshDatabase;

    private const TIERS = [['min_quantity' => 6, 'price' => 85000], ['min_quantity' => 12, 'price' => 80000]];

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
            'name' => 'Daster Aluna',
            'weight' => 300,
            'is_active' => 1,
            'price' => '95.000',
            'stock' => 100,
        ], $overrides);
    }

    public function test_admin_sets_validates_and_clears_wholesale_tiers(): void
    {
        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk()->assertSee('Harga grosir')->assertSee('Tambah tingkat');

        // Harga grosir harus lebih murah dari harga normal.
        $this->post(route('admin.products.store'), $this->payload(['wholesale_prices' => [['min_quantity' => 6, 'price' => '95.000']]]))
            ->assertSessionHasErrors('wholesale_prices');

        // Makin banyak makin murah.
        $this->post(route('admin.products.store'), $this->payload(['wholesale_prices' => [
            ['min_quantity' => 6, 'price' => '80.000'], ['min_quantity' => 12, 'price' => '85.000'],
        ]]))->assertSessionHasErrors('wholesale_prices');

        // Minimal beli tidak boleh sama.
        $this->post(route('admin.products.store'), $this->payload(['wholesale_prices' => [
            ['min_quantity' => 6, 'price' => '85.000'], ['min_quantity' => 6, 'price' => '80.000'],
        ]]))->assertSessionHasErrors('wholesale_prices.1.min_quantity');

        $this->post(route('admin.products.store'), $this->payload(['wholesale_prices' => [
            ['min_quantity' => 12, 'price' => '80.000'], ['min_quantity' => 6, 'price' => '85.000'],
        ]]))->assertSessionHasNoErrors();

        $product = Product::firstWhere('name', 'Daster Aluna');
        $this->assertSame(self::TIERS, $product->wholesaleTiers());
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('80.000');

        // Tanpa baris = harga grosir dimatikan.
        $this->put(route('admin.products.update', $product), $this->payload(['stock_original' => 100]))->assertSessionHasNoErrors();
        $this->assertNull($product->refresh()->wholesale_prices);
        $this->assertFalse($product->hasWholesale());
    }

    public function test_tier_price_rules(): void
    {
        $product = Product::factory()->make(['price' => 95000, 'wholesale_prices' => self::TIERS]);

        $this->assertSame(95000, $product->unitPriceFor(95000, 5));
        $this->assertSame(85000, $product->unitPriceFor(95000, 6));
        $this->assertSame(85000, $product->unitPriceFor(95000, 11));
        $this->assertSame(80000, $product->unitPriceFor(95000, 40));
        // Varian yang sudah lebih murah tidak dinaikkan.
        $this->assertSame(78000, $product->unitPriceFor(78000, 12));
        $this->assertSame(['min_quantity' => 12, 'price' => 80000], $product->nextWholesaleTier(6));
        $this->assertSame(6, $product->wholesaleMinQuantity());
        $this->assertSame(80000, $product->lowestWholesalePrice());
    }

    public function test_storefront_shows_wholesale_prices(): void
    {
        $product = Product::factory()->create(['name' => 'Daster Grosir', 'price' => 95000, 'stock' => 50, 'wholesale_prices' => self::TIERS]);
        Product::factory()->create(['name' => 'Daster Eceran']);

        $this->get(route('products.index'))->assertOk()->assertSee('Grosir mulai Rp80.000');
        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('Harga grosir')
            ->assertSee('6–11 pcs')
            ->assertSee('12+ pcs')
            ->assertSee('Rp85.000');
    }

    public function test_cart_and_checkout_apply_wholesale_across_variants(): void
    {
        $customer = User::factory()->create();
        $address = Address::factory()->for($customer)->create();
        $product = Product::factory()->create(['name' => 'Daster Motif', 'wholesale_prices' => self::TIERS]);
        $motifA = ProductVariant::factory()->for($product)->create(['name' => 'Motif A', 'price' => 95000, 'stock' => 50]);
        $motifB = ProductVariant::factory()->for($product)->create(['name' => 'Motif B', 'price' => 95000, 'stock' => 50]);
        $product->syncVariantSummary();
        $other = Product::factory()->create(['name' => 'Daster Lain', 'price' => 90000, 'stock' => 50]);

        $this->actingAs($customer);
        $this->post(route('cart.store', $product), ['variant_id' => $motifA->id, 'quantity' => 3]);
        $this->post(route('cart.store', $product), ['variant_id' => $motifB->id, 'quantity' => 2]);
        $this->post(route('cart.store', $other), ['quantity' => 10]);

        // 5 pcs: belum grosir, ada ajakan menambah 1 pcs lagi.
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('Tambah <strong>1 pcs</strong> lagi (motif boleh campur)', false)
            ->assertDontSee('Hemat harga grosir');

        // 6 pcs dari dua motif: harga grosir untuk keduanya. Produk lain tidak ikut dihitung.
        $this->post(route('cart.store', $product), ['variant_id' => $motifB->id, 'quantity' => 1]);
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('Harga grosir')
            ->assertSee('Hemat harga grosir')
            ->assertSee('Rp60.000'); // 6 × (95.000 − 85.000)
        $this->get(route('checkout.index'))->assertOk()->assertSee('Harga grosir');

        $this->post(route('checkout.store'), ['address_id' => $address->id, 'shipping' => 'jne|REG'])->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame(6 * 85000 + 10 * 90000, $order->subtotal);
        $itemA = $order->items->firstWhere('product_variant_id', $motifA->id);
        $this->assertSame(85000, $itemA->price);
        $this->assertSame(95000, $itemA->retail_price);
        $this->assertTrue($itemA->isWholesale());
        $this->assertNull($order->items->firstWhere('product_id', $other->id)->retail_price);
        $this->assertSame(60000, $order->wholesaleSavings());

        $this->get(route('account.orders.show', $order))->assertOk()->assertSee('Harga grosir');

        $this->actingAs($this->admin);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Harga grosir');
        $this->get(route('admin.orders.index', ['jenis' => 'grosir']))->assertOk()->assertSee($order->code);
    }

    public function test_preorder_products_get_wholesale_prices_too(): void
    {
        $customer = User::factory()->create();
        $address = Address::factory()->for($customer)->create();
        $po = Product::factory()->create(['price' => 95000, 'stock' => 0, 'is_preorder' => true, 'preorder_days' => 14, 'wholesale_prices' => self::TIERS]);

        $this->actingAs($customer)->post(route('cart.store', $po), ['quantity' => 12]);
        $this->post(route('checkout.store'), ['address_id' => $address->id, 'shipping' => 'jne|REG'])->assertRedirect();

        $item = Order::with('items')->sole()->items->sole();
        $this->assertTrue($item->is_preorder);
        $this->assertSame(80000, $item->price);
    }

    public function test_import_cell_parses_wholesale_tiers(): void
    {
        $this->assertNull(CellValue::wholesale('', 5));
        $this->assertSame([], CellValue::wholesale('0', 5));
        $this->assertSame(self::TIERS, CellValue::wholesale('12 = Rp80.000; 6pcs=85000', 5));

        $this->expectException(InvalidCell::class);
        CellValue::wholesale('6=80000; 12=85000', 5);
    }
}
