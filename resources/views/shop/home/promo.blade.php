{{-- Promo: panel blush bermotif bunga dengan stiker diskon + produk diskon (teks diatur di Admin › Tampilan Beranda) --}}
@use('App\Support\Storefront')
@if ($onSale->isNotEmpty())
    @php($maxDiscount = $onSale->max(fn ($p) => $p->discountPercent()))
    <section class="shop-container section-gap" aria-labelledby="home-sale">
        <div class="relative isolate overflow-hidden rounded-[2.5rem] bg-blush-100 px-4 py-10 sm:px-8 lg:px-12 lg:py-14">
            <div class="ornament-bg-light absolute inset-0 -z-10 opacity-80 [mask-image:linear-gradient(to_right,#000,transparent_65%)]" aria-hidden="true"></div>
            <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-12">
                <div class="relative text-center lg:col-span-4 lg:text-left">
                    @if ($badge = Storefront::text('promo_badge'))
                        <span class="absolute -top-6 right-2 grid size-20 rotate-12 place-items-center rounded-full bg-accent-500 text-center text-white shadow-lg shadow-accent-700/25 sm:right-6 lg:-top-8 lg:right-0" aria-hidden="true">
                            <span class="leading-none">
                                @if ($maxDiscount)<span class="block text-[10px] font-bold uppercase">s.d.</span><span class="block font-display text-2xl font-bold">{{ $maxDiscount }}%</span>@else<span class="block px-2 text-[11px] font-bold uppercase">{{ $badge }}</span>@endif
                            </span>
                        </span>
                    @endif
                    @if ($eyebrow = Storefront::text('promo_eyebrow'))
                        <p class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[11px] font-bold tracking-[0.14em] text-accent-600 uppercase">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full rounded-full bg-accent-400 opacity-75 motion-safe:animate-ping"></span><span class="relative inline-flex size-2 rounded-full bg-accent-500"></span></span>
                            {{ $eyebrow }}
                        </p>
                    @endif
                    <h2 id="home-sale" class="display mt-4 text-[2.5rem] leading-[1.04] text-ink sm:text-5xl lg:text-6xl">{{ Storefront::emphasize(Storefront::text('promo_title', 'Lagi diskon')) }}</h2>
                    @if ($text = Storefront::text('promo_text'))<p class="mx-auto mt-4 max-w-sm text-sm leading-relaxed text-stone-600 sm:text-[15px] lg:mx-0">{{ $text }}</p>@endif
                    <a href="{{ Storefront::href(Storefront::text('promo_url', '/produk?promo=1')) }}" class="btn btn-ink btn-pill mt-7 min-h-12 px-7">{{ Storefront::text('promo_button', 'Semua promo') }} <x-icon name="arrow-right" class="size-4" /></a>
                </div>
                <div class="grid grid-cols-2 gap-x-3 gap-y-9 sm:gap-x-5 md:grid-cols-4 lg:col-span-8">
                    @foreach ($onSale as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
