@php
    $hero = \App\Support\Storefront::hero();
    // Foto hero tampil di dalam bingkai lengkung (potret). Tanpa foto sendiri: ilustrasi versi potret.
    $heroImage = $hero['custom'] ? $hero['image'] : ($hero['mobile_image'] ?? $hero['image']);
    $heroMobile = $hero['custom'] ? $hero['mobile_image'] : null;
    $pick = $bestSellers->first();
@endphp
<x-layouts.shop>
    <x-slot:head>
        {!! \App\Support\StructuredData::script(\App\Support\StructuredData::website()) !!}
        <link rel="preload" as="image" href="{{ $heroImage }}" @if ($heroMobile) media="(min-width: 768px)" @endif>
        @if ($heroMobile)
            <link rel="preload" as="image" href="{{ $heroMobile }}" media="(max-width: 767px)">
        @endif
    </x-slot:head>

    {{-- Hero: latar blush bermotif bunga kain daster, foto dalam bingkai "daun", tepi bawah renda (diatur di Admin › Tampilan Beranda) --}}
    <section class="relative isolate overflow-hidden bg-blush-50">
        <div class="ornament-bg-light absolute inset-0 -z-10 [mask-image:radial-gradient(ellipse_at_70%_45%,#000,transparent_78%)]" aria-hidden="true"></div>
        <div class="absolute -top-32 -left-24 -z-10 size-[26rem] rounded-full bg-brand-100/70 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -right-24 -bottom-40 -z-10 size-[30rem] rounded-full bg-blush-200/60 blur-3xl" aria-hidden="true"></div>

        <div class="shop-container grid grid-cols-1 items-center gap-x-10 gap-y-12 pt-7 pb-14 md:grid-cols-12 md:py-14 lg:gap-x-16 lg:py-20">
            {{-- Bingkai foto --}}
            <div class="relative mx-auto w-full max-w-[21rem] sm:max-w-[25rem] md:col-span-6 md:col-start-7 md:row-start-1 md:max-w-none lg:col-span-5 lg:col-start-8">
                <div class="arch absolute inset-0 translate-x-3 translate-y-3 bg-brand-300/70 sm:translate-x-4 sm:translate-y-4" aria-hidden="true"></div>
                <picture class="arch relative block aspect-[4/5] overflow-hidden bg-sand-200 shadow-2xl shadow-blush-700/15 ring-4 ring-white">
                    @if ($heroMobile)
                        <source media="(max-width: 767px)" srcset="{{ $heroMobile }}">
                    @endif
                    <img src="{{ $heroImage }}" alt="{{ \App\Support\Storefront::plain($hero['title']) }}" fetchpriority="high" decoding="async" class="size-full object-cover object-[50%_25%]">
                </picture>

                {{-- Stempel lingkar motto + monogram CC --}}
                <div class="absolute -top-5 -left-3 grid size-[5.75rem] place-items-center rounded-full bg-white text-brand-600 shadow-lg shadow-blush-700/10 ring-1 ring-brand-200 sm:-left-7 sm:size-28" aria-hidden="true">
                    <svg viewBox="0 0 100 100" class="absolute inset-0 size-full motion-safe:animate-[spin_28s_linear_infinite]">
                        <defs><path id="hero-stamp" d="M50 50m-37 0a37 37 0 1 1 74 0a37 37 0 1 1-74 0"/></defs>
                        <text class="fill-current font-sans text-[7.4px] font-bold tracking-[0.12em] uppercase"><textPath href="#hero-stamp" textLength="228" lengthAdjust="spacing">{{ config('shop.motto') }} • {{ config('shop.name') }} •</textPath></text>
                    </svg>
                    <x-logo variant="mark" class="h-auto w-[34%]" />
                </div>

                {{-- Stiker keunggulan (ciri daster: adem, all size, busui) --}}
                <span class="absolute top-[18%] -right-2 rotate-6 rounded-full bg-white px-3.5 py-2 text-[11px] font-bold text-ink shadow-lg shadow-blush-700/10 ring-1 ring-blush-100 sm:-right-6 sm:text-xs" aria-hidden="true">
                    <span class="flower-dot mr-1 size-3 align-[-2px] text-blush-400"></span>Rayon adem
                </span>
                <span class="absolute top-[46%] -right-3 -rotate-3 rounded-full bg-ink px-3.5 py-2 text-[11px] font-bold text-white shadow-lg sm:-right-8 sm:text-xs" aria-hidden="true">LD 120 · all size</span>

                {{-- Produk pilihan (best seller teratas) --}}
                @if ($pick)
                    <a href="{{ route('products.show', $pick) }}"
                       class="group absolute -bottom-6 left-1/2 flex w-[calc(100%-1.5rem)] max-w-72 -translate-x-1/2 items-center gap-3 rounded-2xl bg-white/95 p-2 pr-4 text-left shadow-xl shadow-blush-700/10 ring-1 ring-blush-100 backdrop-blur transition hover:-translate-y-0.5 md:-left-10 md:translate-x-0">
                        <x-product-image :product="$pick" class="size-14 shrink-0 rounded-xl" />
                        <span class="min-w-0">
                            <span class="block text-[10px] font-bold tracking-[0.16em] text-blush-600 uppercase">Paling laris</span>
                            <span class="block truncate text-sm font-semibold text-ink">{{ $pick->name }}</span>
                            <span class="flex items-baseline gap-1.5 text-sm font-bold text-ink">@rupiah($pick->price)
                                @if ($pick->isOnSale())<span class="text-[11px] font-medium text-stone-400 line-through">@rupiah($pick->compare_at_price)</span>@endif
                            </span>
                        </span>
                        <x-icon name="arrow-right" class="ml-auto size-4 shrink-0 text-stone-400 transition group-hover:translate-x-0.5 group-hover:text-ink" />
                    </a>
                @endif
            </div>

            {{-- Teks --}}
            <div class="min-w-0 text-center md:col-span-6 md:row-start-1 md:text-left lg:col-span-7">
                @if ($hero['eyebrow'])
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3.5 py-1.5 text-[11px] font-bold tracking-[0.14em] text-brand-700 uppercase ring-1 ring-brand-200">
                        <span class="flower-dot size-3 text-brand-400"></span>{{ $hero['eyebrow'] }}
                    </p>
                @endif
                <h1 class="display mt-5 text-[2.6rem] leading-[1.04] text-ink sm:text-6xl lg:text-7xl xl:text-[5rem]">{{ \App\Support\Storefront::emphasize($hero['title'], lastWord: true) }}</h1>
                @if ($hero['subtitle'])
                    <p class="mx-auto mt-5 max-w-lg text-[15px] leading-relaxed text-stone-600 sm:text-lg md:mx-0">{{ $hero['subtitle'] }}</p>
                @endif
                @if ($hero['primary'] || $hero['secondary'])
                    <div class="mt-8 flex flex-col gap-3 min-[400px]:flex-row min-[400px]:justify-center md:justify-start">
                        @foreach (array_filter([$hero['primary'], $hero['secondary']]) as $cta)
                            <a href="{{ $cta['url'] }}" @class([
                                'btn btn-pill group min-h-12 px-7 whitespace-nowrap',
                                'btn-ink' => $loop->first,
                                'btn-outline bg-white/70' => ! $loop->first,
                            ])>{{ $cta['label'] }} @if ($loop->first)<x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />@endif</a>
                        @endforeach
                    </div>
                @endif

                {{-- Pintasan koleksi --}}
                @if ($activities->isNotEmpty() && config('shop.storefront.hero_rail', true))
                    <div class="mt-10 border-t border-dashed border-blush-300/70 pt-5 lg:mt-12">
                        <p class="eyebrow text-stone-500">Pilih koleksimu</p>
                        <ul class="no-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 md:mx-0 md:flex-wrap md:overflow-visible md:px-0">
                            @foreach ($activities as $tile)
                                <li class="shrink-0">
                                    <a href="{{ $tile['url'] }}" class="group inline-flex min-h-10 items-center gap-2 rounded-full bg-white py-1.5 pr-4 pl-1.5 text-[13px] font-semibold text-ink shadow-xs ring-1 ring-blush-100 transition hover:bg-ink hover:text-white hover:ring-ink">
                                        <span class="grid size-7 place-items-center rounded-full bg-blush-100 text-blush-600 transition group-hover:bg-white/15 group-hover:text-brand-300">
                                            <x-category-icon :name="$tile['icon']" :for="$tile['for']" class="size-4" />
                                        </span>
                                        {{ $tile['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>
    <div class="scallop -mt-px text-blush-50" aria-hidden="true"></div>

    {{-- Seksi di bawah hero: urutan & tampil/sembunyi diatur di Admin › Tampilan Beranda --}}
    @foreach (\App\Support\Storefront::sections() as $section)
        @include('shop.home.'.$section, ['first' => $loop->first])
    @endforeach
</x-layouts.shop>
