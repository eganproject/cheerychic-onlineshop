<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_uses_cheerychic_branding(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<title>Cheery Chic', false)
            ->assertSee(config('shop.motto'))
            ->assertSee(config('shop.hashtag'))
            ->assertDontSee('MGA')
            ->assertDontSee('Media Gudang');
    }

    public function test_header_mega_menu_lists_subcategories(): void
    {
        $running = Category::factory()->create(['name' => 'Running', 'slug' => 'running']);
        Category::factory()->create(['name' => 'Running Belt', 'slug' => 'running-belt', 'parent_id' => $running->id]);

        $this->get(route('home'))->assertOk()
            ->assertSee('id="mega-menu"', false)
            ->assertSeeInOrder(['Running', 'Running Belt'])
            ->assertSee(route('products.index', ['kategori' => 'running-belt']), false);
    }

    public function test_product_image_can_point_to_a_remote_url(): void
    {
        Storage::fake('public');
        $url = 'https://down-id.img.susercontent.com/file/contoh-foto';
        $product = Product::factory()->create();
        $image = $product->images()->create(['path' => $url, 'sort_order' => 0]);

        $this->assertTrue($image->isRemote());
        $this->assertSame($url, $image->url());
        $this->get(route('products.show', $product))->assertOk()->assertSee($url, false);

        $image->delete();
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }
}
