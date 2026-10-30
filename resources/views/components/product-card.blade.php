{{--
    Kartu produk Cheery Chic: foto potret 3:4 berbingkai "daun" (.arch-soft) di latar krem,
    info rata tengah, lalu tombol tambah yang selalu terlihat (produk bervarian
    diarahkan ke halaman produk untuk memilih motif/varian).
    rank: nomor peringkat emas di pojok kiri atas foto (dipakai di Best Seller).
--}}
@props(['product', 'quickAdd' => true, 'eager' => false, 'rank' => null])
@php
    $soldOut = ! $product->inStock();
    $isNew = ! $product->isOnSale() && $product->created_at?->gt(now()->subDays(30));
    $variantLabel = \Illuminate\Support\Str::lower($product->variant_label ?: 'varian');
@endphp
<article {{ $attributes->merge(['class' => 'group relative flex flex-col text-center']) }}>
    <div class="relative">
        <x-product-image :product="$product" :eager="$eager" class="arch-soft aspect-[3/4] [&_img]:object-top {{ $soldOut ? 'opacity-60' : '' }}" />

        @if ($rank)
            <span class="pointer-events-none absolute top-2.5 left-2.5 grid size-9 place-items-center rounded-full bg-brand-400 font-display text-lg leading-none font-bold text-ink italic shadow-md ring-2 ring-white sm:top-3 sm:left-3 sm:size-10 sm:text-xl" aria-label="Peringkat {{ $rank }}">{{ $rank }}</span>
        @endif

        <div class="pointer-events-none absolute bottom-2.5 left-2.5 flex flex-col items-start gap-1 sm:bottom-3 sm:left-3">
            @if ($soldOut)
                <span class="tag bg-ink text-white">Stok habis</span>
            @elseif ($product->isOnSale())
                <span class="tag bg-accent-500 text-white">-{{ $product->discountPercent() }}%</span>
            @elseif ($isNew)
                <span class="tag bg-blush-100 text-blush-700 shadow-xs">Baru</span>
            @endif
        </div>

        <div class="absolute right-1.5 bottom-1.5 z-10 rounded-full bg-white/90 shadow-sm backdrop-blur-sm sm:right-2 sm:bottom-2">
            <x-wishlist-button :product="$product" />
        </div>
    </div>

    <div class="flex flex-1 flex-col items-center px-0.5 pt-3 sm:px-1 sm:pt-4">
        @if ($product->relationLoaded('category') && $product->category)
            <p class="max-w-full truncate text-[10px] font-bold tracking-[0.14em] text-brand-700 uppercase sm:text-[11px]">{{ $product->category->name }}</p>
        @endif
        <h3 class="mt-1 text-[13px] leading-snug font-semibold text-ink line-clamp-2 sm:text-[15px]">
            <a href="{{ route('products.show', $product) }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <div class="mt-auto flex flex-wrap items-baseline justify-center gap-x-1.5 pt-2">
            <p @class(['text-sm font-bold tracking-tight sm:text-base', 'text-accent-600' => $product->isOnSale(), 'text-ink' => ! $product->isOnSale()])>@rupiah($product->price)</p>
            @if ($product->isOnSale())
                <p class="text-[11px] text-stone-400 line-through sm:text-xs">@rupiah($product->compare_at_price)</p>
            @endif
        </div>
        @if ($product->review_count > 0)
            <p class="mt-1 flex items-center gap-1 text-[11px] text-stone-500 sm:text-xs">
                <x-icon name="star" class="size-3.5 fill-brand-400 text-brand-400" />
                <span class="font-medium text-ink">{{ number_format($product->rating_avg, 1, ',', '.') }}</span>
                <span>({{ $product->review_count }})</span>
            </p>
        @endif

        @if ($quickAdd)
            @php($btn = 'relative z-10 mt-3 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-full border text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 sm:h-10 sm:text-[13px]')
            @if ($soldOut)
                <span class="{{ $btn }} border-stone-200 text-stone-400">Stok habis</span>
            @elseif ($product->has_variants)
                <a href="{{ route('products.show', $product) }}" class="{{ $btn }} border-brand-300 bg-brand-50 text-ink hover:border-brand-400 hover:bg-brand-400"
                   aria-label="Pilih {{ $variantLabel }} {{ $product->name }}">
                    {{ $variantLabel === 'pilihan' ? 'Lihat pilihan' : 'Pilih '.$variantLabel }} <x-icon name="chevron-right" class="size-3.5" />
                </a>
            @else
                <form method="POST" action="{{ route('cart.store', $product) }}" data-cart-add class="w-full">
                    @csrf
                    <button class="{{ $btn }} border-brand-300 bg-brand-50 text-ink hover:border-brand-400 hover:bg-brand-400" aria-label="Tambah {{ $product->name }} ke keranjang">
                        <x-icon name="plus" class="size-3.5" /> Keranjang
                    </button>
                </form>
            @endif
        @endif
    </div>
</article>
