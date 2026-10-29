<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Pre Order: stok tetap dipakai sebagai kuota PO, hanya waktu prosesnya yang lebih lama.
            $table->boolean('is_preorder')->default(false)->after('is_featured')->index();
            $table->unsignedSmallInteger('preorder_days')->nullable()->after('is_preorder');
            $table->string('preorder_note', 160)->nullable()->after('preorder_days');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Disalin saat checkout, supaya pesanan lama tidak berubah bila status PO produk diganti.
            $table->boolean('is_preorder')->default(false)->after('subtotal');
            $table->unsignedSmallInteger('preorder_days')->nullable()->after('is_preorder');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['is_preorder', 'preorder_days']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_preorder']);
            $table->dropColumn(['is_preorder', 'preorder_days', 'preorder_note']);
        });
    }
};
