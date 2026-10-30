<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Harga grosir bertingkat: [{"min_quantity": 6, "price": 85000}, ...], urut naik.
            $table->json('wholesale_prices')->nullable()->after('compare_at_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Harga satuan normal sebelum grosir; null bila baris tidak mendapat harga grosir.
            $table->unsignedBigInteger('retail_price')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('retail_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('wholesale_prices');
        });
    }
};
