@php
    $title = \App\Support\Storefront::plain(\App\Support\Storefront::text('best_title', 'Best Seller'));
    $intro = $manual
        ? 'Pilihan favorit tim Cheery Chic, daster dan one set yang paling sering dicari pelanggan kami.'
        : 'Daster dan one set yang paling banyak dibeli pelanggan Cheery Chic dalam 90 hari terakhir.';
    $rankFor = fn ($product, int $position) => $position <= 10 && ($manual || ($product->sold_count ?? 0) > 0) ? $position : null;
@endphp
<x-layouts.shop :title="$title" :description="$intro">
    {{-- Kepala halaman --}}
    <section class="border-b border-stone-200/70 bg-white">
        <div class="shop-container pt-6 pb-6 lg:pt-10 lg:pb-8">
            <nav class="flex flex-wrap items-center gap-1.5 text-xs text-stone-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-ink">Beranda</a>
                <x-icon name="chevron-right" class="size-3 text-stone-300" />
                <a href="{{ route('products.index') }}" class="hover:text-ink">Produk</a>
                <x-icon name="chevron-right" class="size-3 text-stone-300" />
                <span class="text-ink">{{ $title }}</span>
            </nav>

            <div class="mt-6 flex flex-col gap-4 lg:mt-8 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
                <div class="min-w-0">
                    <p class="kicker">{{ \App\Support\Storefront::text('best_eyebrow') ?: 'Paling diminati' }}</p>
                    <h1 class="display mt-3 text-4xl sm:text-5xl lg:text-6xl">{{ $title }}</h1>
                    <p class="mt-3 max-w-xl text-[15px] leading-relaxed text-stone-600">{{ $intro }}</p>
                </div>
                <a href="{{ route('products.index') }}" class="link-arrow shrink-0">Lihat semua produk <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
    </section>

    {{-- Daftar berperingkat (urutan mengikuti Admin › Tampilan Beranda) --}}
    @if ($ranked->isNotEmpty())
        <section class="shop-container pt-8 lg:pt-12" aria-label="Peringkat {{ $title }}">
            <div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-6 sm:gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($ranked as $product)
                    <x-product-tile :product="$product" :eager="$loop->index < 4" :rank="$rankFor($product, $loop->iteration)" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Sisa katalog --}}
    <section id="lainnya" class="shop-container section-gap scroll-mt-24">
        @if ($others->isNotEmpty())
            <div class="flex items-end justify-between gap-4 border-t border-stone-200/70 pt-10 lg:pt-14">
                <div>
                    <p class="kicker">Masih banyak lagi</p>
                    <h2 class="section-title">Produk lainnya</h2>
                </div>
                <a href="{{ route('products.index') }}" class="link-arrow">Katalog <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <div class="mt-7 grid grid-cols-2 gap-x-3 gap-y-8 sm:mt-9 sm:gap-x-6 sm:gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($others as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-14">{{ $others->links() }}</div>
        @elseif ($ranked->isEmpty())
            <x-empty-state title="Belum ada produk" />
        @endif
    </section>
</x-layouts.shop>
