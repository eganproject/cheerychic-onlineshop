<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ubah jumlah & hapus di halaman keranjang lewat JS (tanpa reload). */
class CartPageAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_update_returns_rendered_cart_and_count(): void
    {
        $product = Product::factory()->create(['name' => 'Daster Rayon', 'price' => 150000, 'stock' => 10]);
        $this->post(route('cart.store', $product), ['quantity' => 1]);

        $response = $this->patchJson(route('cart.update', (string) $product->id), ['quantity' => 3])
            ->assertOk()
            ->assertJson(['type' => null, 'message' => null, 'count' => 3]);

        $html = $response->json('html');
        $this->assertStringContainsString('Daster Rayon', $html);
        $this->assertStringContainsString('3 item', $html);
        $this->assertStringContainsString('data-cart-line', $html);
        $this->assertStringContainsString('450.000', $html);
    }

    public function test_json_update_beyond_stock_is_capped_with_error_toast(): void
    {
        $product = Product::factory()->create(['name' => 'Sajadah', 'stock' => 2]);
        $this->post(route('cart.store', $product));

        $this->patchJson(route('cart.update', (string) $product->id), ['quantity' => 5])
            ->assertOk()
            ->assertJson(['type' => 'error', 'message' => 'Stok Sajadah hanya tersisa 2.', 'count' => 2]);
    }

    public function test_quantity_zero_and_destroy_remove_line_and_show_empty_state(): void
    {
        $a = Product::factory()->create(['name' => 'Daster A', 'stock' => 5]);
        $b = Product::factory()->create(['name' => 'Daster B', 'stock' => 5]);
        $this->post(route('cart.store', $a), ['quantity' => 2]);
        $this->post(route('cart.store', $b));

        $this->patchJson(route('cart.update', (string) $a->id), ['quantity' => 0])
            ->assertOk()
            ->assertJson(['type' => 'success', 'message' => 'Daster A dihapus dari keranjang.', 'count' => 1]);

        $response = $this->deleteJson(route('cart.destroy', (string) $b->id))
            ->assertOk()
            ->assertJson(['message' => 'Daster B dihapus dari keranjang.', 'count' => 0]);

        $this->assertStringContainsString('Keranjangmu masih kosong', $response->json('html'));
    }

    public function test_json_update_for_missing_line_refreshes_instead_of_404(): void
    {
        $this->patchJson(route('cart.update', '999'), ['quantity' => 1])
            ->assertOk()
            ->assertJson(['type' => 'warning', 'count' => 0]);

        $this->patch(route('cart.update', '999'), ['quantity' => 1])->assertNotFound();
    }

    public function test_plain_forms_still_redirect_back(): void
    {
        $product = Product::factory()->create(['name' => 'Bergo', 'stock' => 5]);
        $this->post(route('cart.store', $product));

        $this->from(route('cart.index'))
            ->patch(route('cart.update', (string) $product->id), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->from(route('cart.index'))
            ->delete(route('cart.destroy', (string) $product->id))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('success', 'Bergo dihapus dari keranjang.');
    }

    public function test_cart_page_opts_into_ajax(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->post(route('cart.store', $product));

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('id="cart-content"', false)
            ->assertSee('data-cart-line', false)
            ->assertSee('data-step="1"', false);
    }
}
