{{-- Best Seller: judul rata tengah + kisi produk berperingkat (HP 4 produk, tablet/desktop 8) --}}
@if ($bestSellers->isNotEmpty())
    <section class="shop-container section-gap" aria-labelledby="home-best">
        <div class="section-head">
            <span class="ornament-mark" aria-hidden="true"><span class="flower-dot"></span></span>
            @if ($eyebrow = \App\Support\Storefront::text('best_eyebrow'))<p class="eyebrow mt-3 text-brand-700">{{ $eyebrow }}</p>@endif
            <h2 id="home-best" class="section-title">{{ \App\Support\Storefront::emphasize(\App\Support\Storefront::text('best_title', 'Best Seller')) }}</h2>
            <p class="mt-3 max-w-md text-sm text-stone-500 sm:text-[15px]">Motif yang paling sering bolak-balik masuk keranjang. Stok cepat habis, jangan ditunda ya!</p>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-x-3 gap-y-9 sm:gap-x-5 md:grid-cols-4 lg:mt-12 lg:gap-x-7 lg:gap-y-12">
            @php($shown = $bestSellers->count() >= 8 ? 8 : ($bestSellers->count() >= 4 ? 4 : $bestSellers->count()))
            @foreach ($bestSellers->take($shown) as $product)
                <x-product-card :product="$product" :eager="$loop->index < 4" :rank="$loop->iteration <= 10 && (\App\Services\Storefront\BestSellers::isManual() || ($product->sold_count ?? 0) > 0) ? $loop->iteration : null"
                                :class="$loop->iteration > 4 ? 'max-md:hidden' : ''" />
            @endforeach
        </div>
        <div class="mt-10 text-center lg:mt-12">
            <a href="{{ route('products.best-sellers') }}" class="btn btn-outline btn-pill min-h-12 px-7">Lihat semua best seller <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </section>
@else
    <section class="shop-container section-gap">
        <x-empty-state title="Belum ada produk" />
    </section>
@endif
