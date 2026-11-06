<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Services\Storefront\BestSellers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;

/**
 * Kosongkan data toko kecuali katalog, misalnya sebelum mulai berjualan sungguhan.
 * Dipertahankan: produk (foto, varian, stok & riwayat stok), kategori, koleksi,
 * akun staf/admin, dan pengaturan toko. Selain itu dihapus.
 *   php artisan shop:clear-data
 */
#[Signature('shop:clear-data {--force : Jalankan tanpa konfirmasi}')]
#[Description('Hapus pesanan, pelanggan, ulasan, voucher, banner, artikel & log; produk dan akun staf dipertahankan')]
class ClearShopDataCommand extends Command
{
    /** Urutan = anak dulu supaya foreign key tidak menolak. */
    private const TABLES = [
        'order_status_histories' => 'Riwayat status pesanan',
        'payments' => 'Pembayaran',
        'order_items' => 'Item pesanan',
        'product_reviews' => 'Ulasan produk',
        'orders' => 'Pesanan',
        'wishlist_items' => 'Wishlist',
        'addresses' => 'Alamat pelanggan',
        'vouchers' => 'Voucher',
        'banners' => 'Banner promo',
        'article_product' => 'Produk terkait artikel',
        'articles' => 'Artikel',
        'article_categories' => 'Kategori artikel',
        'activity_logs' => 'Log aktivitas',
        'product_imports' => 'Riwayat impor produk',
    ];

    public function handle(): int
    {
        $customers = DB::table('users')->where('role', UserRole::Customer->value);

        $this->components->info('Data berikut akan dihapus permanen:');
        foreach (self::TABLES as $table => $label) {
            $this->components->twoColumnDetail($label, (string) DB::table($table)->count());
        }
        $this->components->twoColumnDetail('Akun pelanggan', (string) $customers->count());
        $this->newLine();
        $this->line('  Dipertahankan: produk, foto, varian, stok, kategori, koleksi, akun staf, pengaturan toko.');
        $this->newLine();

        if (! $this->option('force') && ! confirm('Lanjut hapus? Pastikan sudah backup (php artisan shop:backup).', default: false)) {
            $this->components->warn('Dibatalkan, tidak ada yang dihapus.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($customers) {
            foreach (array_keys(self::TABLES) as $table) {
                DB::table($table)->delete();
            }

            $customerIds = (clone $customers)->pluck('id');
            DB::table('sessions')->whereIn('user_id', $customerIds)->delete();
            DB::table('password_reset_tokens')->whereIn('email', (clone $customers)->pluck('email'))->delete();
            (clone $customers)->delete();

            // Ringkasan rating ikut kosong karena ulasannya sudah dihapus.
            DB::table('products')->update(['rating_avg' => 0, 'review_count' => 0]);
        });

        Cache::forget(BestSellers::CACHE_KEY);

        $this->components->info('Selesai. Data selain produk sudah dikosongkan.');

        return self::SUCCESS;
    }
}
