<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClearShopDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_only_fills_catalog_and_staff(): void
    {
        $this->seed();

        $this->assertTrue(Product::count() > 0);
        $this->assertTrue(ProductCollection::count() > 0);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, User::where('role', UserRole::Customer)->count());
        $this->assertSame(0, DB::table('vouchers')->count());
        $this->assertSame(0, DB::table('product_reviews')->count());
        $this->assertSame(0, DB::table('articles')->count());
    }

    public function test_catalog_seeder_creates_no_accounts(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(5, Product::count());
        $this->assertTrue(ProductCollection::count() > 0);
        $this->assertSame(0, User::count());
    }

    public function test_clear_data_keeps_products_and_staff_only(): void
    {
        $this->seed();
        Order::withoutEvents(fn () => $this->seed(DemoOrderSeeder::class));
        $this->seed(ArticleSeeder::class);
        DB::table('vouchers')->insert(['code' => 'TES', 'name' => 'Tes', 'type' => 'fixed', 'value' => 1000, 'created_at' => now(), 'updated_at' => now()]);
        Product::query()->update(['rating_avg' => 4.5, 'review_count' => 3]);

        $products = Product::count();
        $stock = Product::sum('stock');
        $staff = User::staff()->count();
        $this->assertTrue(Order::count() > 0);

        $this->artisan('shop:clear-data', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Order::count());
        $this->assertSame(0, DB::table('order_items')->count());
        $this->assertSame(0, DB::table('vouchers')->count());
        $this->assertSame(0, DB::table('articles')->count());
        $this->assertSame(0, User::where('role', UserRole::Customer)->count());
        $this->assertSame($products, Product::count());
        $this->assertEquals($stock, Product::sum('stock'));
        $this->assertSame($staff, User::staff()->count());
        $this->assertSame(0, (int) Product::sum('review_count'));
        $this->assertDatabaseHas('users', ['email' => 'admin@cheerychic.test']);
    }

    public function test_clear_data_can_be_cancelled(): void
    {
        $this->seed();
        Order::withoutEvents(fn () => $this->seed(DemoOrderSeeder::class));
        $orders = Order::count();

        $this->artisan('shop:clear-data')
            ->expectsConfirmation('Lanjut hapus? Pastikan sudah backup (php artisan shop:backup).', 'no')
            ->assertFailed();

        $this->assertSame($orders, Order::count());
    }
}
