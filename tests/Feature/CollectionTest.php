<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_collection_with_products(): void
    {
        $socks = Product::factory()->create(['name' => 'Kaos Kaki Lari']);
        $bottle = Product::factory()->create(['name' => 'Botol Sport']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.collections.store'), [
                'name' => 'Running',
                'tagline' => 'Lari lebih nyaman',
                'tone' => 'volt',
                'icon' => 'busui',
                'is_active' => '1',
                'show_on_home' => '1',
                'products' => [$socks->id, $bottle->id],
            ])->assertRedirect(route('admin.collections.index'));

        $collection = ProductCollection::where('slug', 'running')->firstOrFail();
        $this->assertSame('volt', $collection->tone);
        $this->assertEqualsCanonicalizing([$socks->id, $bottle->id], $collection->products()->pluck('products.id')->all());

        $this->get(route('admin.collections.index'))->assertOk()->assertSee('Running');
        $this->get(route('admin.collections.edit', $collection))->assertOk()->assertSee('Kaos Kaki Lari');
    }

    public function test_update_keeps_products_when_form_does_not_manage_them(): void
    {
        $collection = ProductCollection::factory()->create();
        $collection->products()->attach(Product::factory()->create());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.collections.update', $collection), ['name' => 'Baru', 'is_active' => '1'])->assertRedirect();
        $this->assertSame(1, $collection->products()->count());

        $this->actingAs($admin)->put(route('admin.collections.update', $collection->fresh()), ['name' => 'Baru', 'slug' => 'baru', 'products_managed' => '1'])->assertRedirect();
        $this->assertSame(0, $collection->products()->count());
        $this->assertFalse($collection->fresh()->is_active);
    }

    public function test_product_form_assigns_many_collections(): void
    {
        $running = ProductCollection::factory()->create(['name' => 'Running']);
        $workout = ProductCollection::factory()->create(['name' => 'Workout']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()->assertSee('Koleksi');

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Kaos Kaki', 'price' => '39.000', 'stock' => 5, 'weight' => 60, 'is_active' => '1',
            'collection_ids' => [$running->id, $workout->id],
        ])->assertSessionHasNoErrors();

        $product = Product::where('name', 'Kaos Kaki')->firstOrFail();
        $this->assertEqualsCanonicalizing([$running->id, $workout->id], $product->collections()->pluck('collections.id')->all());

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => 'Kaos Kaki', 'price' => '39000', 'stock' => 5, 'stock_original' => 5, 'weight' => 60, 'is_active' => '1',
            'collection_ids' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $product->collections()->count());
    }

    public function test_catalog_filters_by_collection_across_categories(): void
    {
        $socksCategory = Category::factory()->create(['name' => 'Kaos Kaki']);
        $bottleCategory = Category::factory()->create(['name' => 'Botol']);
        $running = ProductCollection::factory()->create(['name' => 'Running', 'slug' => 'running', 'tagline' => 'Lari lebih jauh']);

        $socks = Product::factory()->create(['name' => 'Kaos Kaki Lari', 'category_id' => $socksCategory->id]);
        $bottle = Product::factory()->create(['name' => 'Botol Sport', 'category_id' => $bottleCategory->id]);
        Product::factory()->create(['name' => 'Matras Yoga', 'category_id' => $bottleCategory->id]);
        $running->products()->attach([$socks->id, $bottle->id]);

        $this->get(route('products.index', ['koleksi' => 'running']))->assertOk()
            ->assertSee('Lari lebih jauh')
            ->assertSee('Kaos Kaki Lari')
            ->assertSee('Botol Sport')
            ->assertDontSee('Matras Yoga');

        $this->get(route('products.index', ['koleksi' => 'running', 'kategori' => $socksCategory->slug]))->assertOk()
            ->assertSee('Kaos Kaki Lari')
            ->assertDontSee('Botol Sport');

        $this->get(route('products.show', $socks))->assertOk()->assertSee('Cocok untuk');
    }

    public function test_inactive_collection_is_ignored(): void
    {
        ProductCollection::factory()->create(['slug' => 'lama', 'is_active' => false]);
        Product::factory()->create(['name' => 'Produk Lain']);

        $this->get(route('products.index', ['koleksi' => 'lama']))->assertOk()->assertSee('Produk Lain');
    }

    public function test_homepage_tiles_use_collections_then_fall_back_to_categories(): void
    {
        Category::factory()->create(['name' => 'Kategori Lama']);
        $product = Product::factory()->create();

        $this->get(route('home'))->assertOk()->assertSee('Kategori Lama');

        $collection = ProductCollection::factory()->create(['name' => 'Hiking Seru', 'sort_order' => 0]);
        $collection->products()->attach($product);
        ProductCollection::factory()->create(['name' => 'Tersembunyi', 'show_on_home' => false]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Hiking Seru')
            ->assertSee(route('products.index', ['koleksi' => $collection->slug]), false)
            ->assertSee('1 produk');
    }

    public function test_warehouse_staff_without_catalog_permission_cannot_manage_collections(): void
    {
        $this->actingAs(User::factory()->staff(StaffRole::CustomerService)->create())
            ->get(route('admin.collections.index'))->assertForbidden();
    }
}
