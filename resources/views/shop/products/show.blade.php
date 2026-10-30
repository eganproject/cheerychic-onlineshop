<x-layouts.shop :title="$product->name" :description="$product->metaDescription()" :image="$product->imageUrl()" type="product" :bottom-nav="! $product->inStock()" :whatsapp-text="'Halo '.config('shop.name').', saya mau tanya tentang '.$product->name.': '.route('products.show', $product)">
    <x-slot:head>
        <meta property="product:price:amount" content="{{ $product->price }}">
        <meta property="product:price:currency" content="IDR">
        <meta property="product:availability" content="{{ ! $product->inStock() ? 'out of stock' : ($product->isPreorder() ? 'preorder' : 'in stock') }}">
        {!! \App\Support\StructuredData::script(
            \App\Support\StructuredData::product($product, $reviews->getCollection()),
            \App\Support\StructuredData::breadcrumbs(array_values(array_filter([
                ['Beranda', route('home')],
                ['Produk', route('products.index')],
                $product->category?->parent ? [$product->category->parent->name, route('products.index', ['kategori' => $product->category->parent->slug])] : null,
                $product->category ? [$product->category->name, route('products.index', ['kategori' => $product->category->slug])] : null,
                [$product->name, route('products.show', $product)],
            ]))),
        ) !!}
    </x-slot:head>
    @php
        $variants = $product->activeVariants;
        $images = $product->images;
        // Pre Order tidak dibatasi stok: jumlah maksimal = batas per pesanan.
        $preorder = $product->isPreorder();
        $buyable = fn ($v) => $preorder ? \App\Models\Product::PREORDER_MAX_QTY : $v->stock;
        $available = $variants->filter(fn ($v) => $product->variantAvailable($v));
        $pageConfig = [
            'images' => $images->map(fn ($i) => ['id' => $i->id, 'url' => $i->url()])->values(),
            'variants' => $variants->map(fn ($v) => [
                'id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'compare_at_price' => $v->compare_at_price,
                'stock' => $buyable($v), 'image_id' => $v->product_image_id, 'sku' => $v->sku,
            ])->values(),
            // Pilih otomatis bila hanya ada satu varian yang tersedia.
            'selectedId' => old('variant_id') ? (int) old('variant_id') : ($available->count() === 1 ? $available->first()->id : null),
            'price' => $product->price,
            'compareAt' => $product->compare_at_price,
            'stock' => $product->purchasableQuantity(),
            'tiers' => $product->wholesaleTiers(),
        ];
        $tiers = $product->wholesaleTiers();
        $priceRange = $variants->isNotEmpty() && $variants->pluck('price')->unique()->count() > 1;
        $lowStock = config('shop.low_stock_threshold');
        $sportFor = $product->category ? $product->category->name.' '.($product->category->parent?->name) : $product->name;

        $tabs = array_filter([
            'desc' => $product->description ? 'Deskripsi' : null,
            'detail' => 'Detail',
            'ship' => 'Pengiriman',
            'warranty' => 'Garansi',
        ]);
    @endphp

    <div x-data="productPage(@js($pageConfig))">
        <section class="shop-container pt-5 lg:pt-8">
            <nav class="flex flex-wrap items-center gap-1.5 text-xs text-stone-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-ink">Beranda</a>
                <x-icon name="chevron-right" class="size-3 text-stone-300" />
                <a href="{{ route('products.index') }}" class="hover:text-ink">Produk</a>
                @if ($product->category?->parent)
                    <x-icon name="chevron-right" class="size-3 text-stone-300" />
                    <a href="{{ route('products.index', ['kategori' => $product->category->parent->slug]) }}" class="hover:text-ink">{{ $product->category->parent->name }}</a>
                @endif
                @if ($product->category)
                    <x-icon name="chevron-right" class="size-3 text-stone-300" />
                    <a href="{{ route('products.index', ['kategori' => $product->category->slug]) }}" class="hover:text-ink">{{ $product->category->name }}</a>
                @endif
            </nav>

            <div class="mt-5 grid gap-8 lg:mt-8 lg:grid-cols-12 lg:gap-12 xl:gap-16">
                {{-- Galeri --}}
                <div class="min-w-0 lg:col-span-7">
                    <div class="lg:flex lg:gap-4">
                        {{-- Thumbnail vertikal (desktop) --}}
                        @if ($images->count() > 1)
                            <div class="no-scrollbar hidden max-h-[680px] w-20 shrink-0 flex-col gap-3 overflow-y-auto p-0.5 lg:flex">
                                @foreach ($images as $index => $image)
                                    <button type="button" @click="active = {{ $index }}"
                                            class="aspect-square w-full shrink-0 overflow-hidden rounded-xl bg-sand-100 ring-1 transition"
                                            :class="active === {{ $index }} ? 'ring-ink' : 'ring-transparent opacity-60 hover:opacity-100'"
                                            aria-label="Lihat foto {{ $index + 1 }}">
                                        <img src="{{ $image->url() }}" referrerpolicy="no-referrer" onerror="this.style.visibility='hidden'" alt="" loading="lazy" class="size-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="relative min-w-0 flex-1">
                            @if ($images->isEmpty())
                                <x-product-image :product="$product" :eager="true" class="aspect-square overflow-hidden rounded-2xl" />
                            @else
                                {{-- Mobile: geser dengan jari --}}
                                <div x-ref="swipe" class="no-scrollbar -mx-4 flex snap-x snap-mandatory overflow-x-auto bg-sand-100 sm:mx-0 sm:rounded-2xl lg:hidden"
                                     @scroll.debounce.80ms="active = Math.round($el.scrollLeft / $el.clientWidth)"
                                     x-effect="const i = active; $nextTick(() => { if (Math.round($el.scrollLeft / $el.clientWidth) !== i) $el.scrollTo({ left: i * $el.clientWidth, behavior: 'smooth' }) })">
                                    @foreach ($images as $index => $image)
                                        <img src="{{ $image->url() }}" referrerpolicy="no-referrer" onerror="this.style.visibility='hidden'" alt="{{ $product->name }} — foto {{ $index + 1 }}" @if ($index > 0) loading="lazy" @endif decoding="async"
                                             class="aspect-square w-full shrink-0 snap-center object-cover">
                                    @endforeach
                                </div>

                                {{-- Desktop: foto utama --}}
                                <div class="relative hidden aspect-square overflow-hidden rounded-2xl bg-sand-100 lg:block">
                                    @foreach ($images as $index => $image)
                                        <img src="{{ $image->url() }}" referrerpolicy="no-referrer" onerror="this.style.visibility='hidden'" alt="{{ $product->name }} — foto {{ $index + 1 }}"
                                             @if ($index > 0) loading="lazy" x-cloak @endif decoding="async"
                                             x-show="active === {{ $index }}" x-transition.opacity.duration.200ms
                                             class="absolute inset-0 size-full object-cover">
                                    @endforeach
                                    @if ($images->count() > 1)
                                        <div class="absolute right-4 bottom-4 flex gap-2">
                                            <button type="button" @click="next(-1)" class="grid size-10 place-items-center rounded-full bg-white/90 text-ink backdrop-blur transition hover:bg-white" aria-label="Foto sebelumnya"><x-icon name="arrow-left" class="size-[18px]" /></button>
                                            <button type="button" @click="next(1)" class="grid size-10 place-items-center rounded-full bg-white/90 text-ink backdrop-blur transition hover:bg-white" aria-label="Foto berikutnya"><x-icon name="arrow-right" class="size-[18px]" /></button>
                                        </div>
                                    @endif
                                </div>

                                @if ($images->count() > 1)
                                    <div class="mt-3 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                                        @foreach ($images as $index => $image)
                                            <span class="h-1 rounded-full transition-all" :class="active === {{ $index }} ? 'w-6 bg-ink' : 'w-1.5 bg-stone-300'"></span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            <div class="pointer-events-none absolute top-3 left-3 flex flex-col items-start gap-1.5 lg:top-4 lg:left-4">
                                @if ($preorder)
                                    <span class="tag gap-1 bg-sage-600 text-white shadow-xs"><x-icon name="clock" class="size-3" /> Pre Order</span>
                                @endif
                                <template x-if="discount">
                                    <span class="tag bg-accent-500 text-white" x-text="`Hemat ${discount}%`"></span>
                                </template>
                                @if (! $preorder && $product->created_at?->gt(now()->subDays(30)))
                                    <span class="tag bg-white text-ink shadow-xs">Baru</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info produk (lengket di desktop) --}}
                <div class="lg:col-span-5">
                    <div class="lg:sticky lg:top-28">
                        <div class="flex items-center justify-between gap-3">
                            @if ($product->category)
                                <a href="{{ route('products.index', ['kategori' => $product->category->slug]) }}" class="kicker hover:text-ink">{{ $product->category->name }}</a>
                            @endif
                            @if ($product->hasReviews())
                                <a href="#ulasan" class="inline-flex items-center gap-1.5 text-sm">
                                    <x-icon name="star" class="size-4 fill-ink text-ink" />
                                    <span class="font-semibold">{{ number_format($product->rating_avg, 1, ',', '.') }}</span>
                                    <span class="text-stone-500 underline decoration-stone-300 underline-offset-4">{{ $product->review_count }} ulasan</span>
                                </a>
                            @endif
                        </div>

                        <h1 class="display mt-3 text-[1.75rem] leading-[1.12] sm:text-4xl">{{ $product->name }}</h1>

                        {{-- Harga --}}
                        <div class="mt-5 flex flex-wrap items-baseline gap-x-3 gap-y-2">
                            <p class="text-2xl font-semibold tracking-tight sm:text-[1.75rem]">
                                @if ($priceRange)<span x-show="!selected" class="text-sm font-normal text-stone-500">Mulai</span>@endif
                                <span x-text="rupiah(price)">@rupiah($product->price)</span>
                            </p>
                            <p x-show="compareAt" @unless ($product->isOnSale()) x-cloak @endunless class="text-base text-stone-400 line-through" x-text="rupiah(compareAt)">@rupiah($product->compare_at_price)</p>
                            <span x-show="compareAt" @unless ($product->isOnSale()) x-cloak @endunless class="text-sm font-medium" :class="wholesale ? 'text-brand-700' : 'text-accent-600'"
                                  x-text="wholesale ? `Harga grosir · hemat ${rupiah(compareAt - price)}/pcs` : `Hemat ${rupiah(compareAt - price)}`"></span>
                        </div>

                        @if ($tiers)
                            {{-- Harga grosir bertingkat, berlaku untuk semua pembeli. --}}
                            <div class="mt-4 rounded-2xl border border-brand-200 bg-brand-50/70 p-4 text-sm">
                                <p class="flex items-center gap-2 font-semibold text-brand-800">
                                    <x-icon name="tag" class="size-4" /> Harga grosir
                                </p>
                                <p class="mt-0.5 text-xs text-stone-600">
                                    Makin banyak makin hemat.@if ($variants->count() > 1) Jumlah dihitung dari total semua {{ mb_strtolower($product->variant_label ?: 'varian') }} produk ini di keranjang, boleh campur.@endif
                                </p>
                                <ul class="mt-3 grid gap-1.5" role="list">
                                    <li class="flex items-center justify-between rounded-xl px-3 py-2 text-stone-600" :class="!tier && 'bg-white font-semibold text-ink ring-1 ring-brand-300'">
                                        <span>1–{{ $tiers[0]['min_quantity'] - 1 }} pcs</span>
                                        <span>@if ($priceRange)Harga normal @else @rupiah($product->price)@endif</span>
                                    </li>
                                    @foreach ($tiers as $i => $tier)
                                        @php($upper = isset($tiers[$i + 1]) ? '–'.($tiers[$i + 1]['min_quantity'] - 1) : '+')
                                        <li>
                                            <button type="button" @click="useTier(@js($tier))"
                                                    :class="tier && tier.min_quantity === {{ $tier['min_quantity'] }} ? 'bg-white font-semibold text-ink ring-1 ring-brand-300' : 'text-stone-700 hover:bg-white/70'"
                                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left transition">
                                                <span>{{ $tier['min_quantity'] }}{{ $upper }} pcs</span>
                                                <span class="font-semibold text-brand-800">@rupiah($tier['price'])<span class="font-normal text-stone-500">/pcs</span></span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($preorder)
                            {{-- Pre Order: bisa dipesan tanpa batas stok. --}}
                            <div class="mt-4 rounded-2xl border border-sage-200 bg-sage-50 p-4 text-sm">
                                <p class="flex items-center gap-2 font-semibold text-sage-600">
                                    <x-icon name="clock" class="size-4" /> Pre Order · {{ $product->preorderSummary() }}
                                </p>
                                @if ($product->preorder_note)
                                    <p class="mt-1 text-stone-600">{{ $product->preorder_note }}</p>
                                @endif
                                <p class="mt-2 flex items-center gap-2 text-stone-600">
                                    <span class="size-2 rounded-full bg-emerald-500"></span> Bisa dipesan, dibuat khusus untukmu
                                </p>
                            </div>
                        @else
                            <p class="mt-3 flex items-center gap-2 text-sm text-stone-600">
                                <span class="size-2 rounded-full" :class="stock < 1 ? 'bg-stone-400' : (stock <= {{ $lowStock }} ? 'bg-amber-500' : 'bg-emerald-500')"></span>
                                <span x-text="stock < 1 ? 'Stok habis' : (stock <= {{ $lowStock }} ? `Stok terbatas, sisa ${stock}` : 'Stok tersedia, siap dikirim')">
                                    {{ ! $product->inStock() ? 'Stok habis' : ($product->isLowStock() ? 'Stok terbatas, sisa '.$product->stock : 'Stok tersedia, siap dikirim') }}
                                </span>
                            </p>
                        @endif

                        <div class="mt-6 border-t border-stone-200 pt-6">
                            {{-- Varian --}}
                            @if ($variants->isNotEmpty())
                                <div class="mb-6">
                                    <p class="text-sm font-medium">
                                        {{ $product->variant_label ?: 'Varian' }}:
                                        <span class="font-normal text-stone-500" x-text="selected ? selected.name : 'pilih salah satu'"></span>
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($variants as $variant)
                                            @php($variantOk = $product->variantAvailable($variant))
                                            <button type="button" @click="select(@js(['id' => $variant->id, 'stock' => $buyable($variant), 'image_id' => $variant->product_image_id]))"
                                                    @disabled(! $variantOk)
                                                    :aria-pressed="selectedId === {{ $variant->id }}"
                                                    :class="selectedId === {{ $variant->id }} ? 'border-ink ring-1 ring-ink' : 'border-stone-200 hover:border-stone-400'"
                                                    class="relative flex min-h-11 items-center gap-2 rounded-xl border bg-white px-3.5 py-2 text-left text-sm font-medium transition disabled:cursor-not-allowed disabled:border-dashed disabled:text-stone-300">
                                                @if ($variant->image)
                                                    <img src="{{ $variant->image->url() }}" referrerpolicy="no-referrer" alt="" class="size-7 shrink-0 rounded-md object-cover">
                                                @endif
                                                <span class="min-w-0 leading-tight">
                                                    <span @class(['block truncate', 'line-through' => ! $variantOk])>{{ $variant->name }}</span>
                                                    @unless ($variantOk)<span class="block text-[11px] font-normal no-underline">Habis</span>@endunless
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($product->inStock())
                                <form id="add-to-cart" method="POST" action="{{ route('cart.store', $product) }}" data-cart-add
                                      @submit="if (needsVariant) { $event.preventDefault(); $refs.variantHint.classList.remove('hidden'); $refs.variantHint.scrollIntoView({ behavior: 'smooth', block: 'center' }) }">
                                    @csrf
                                    @if ($variants->isNotEmpty())
                                        <input type="hidden" name="variant_id" :value="selectedId ?? ''">
                                    @endif
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-medium">Jumlah</p>
                                        <div class="inline-flex items-center rounded-full border border-stone-200">
                                            <button type="button" @click="qty = Math.max(1, qty - 1)" class="grid size-10 place-items-center rounded-full transition hover:bg-sand-100" aria-label="Kurangi"><x-icon name="minus" class="size-4" /></button>
                                            <input type="number" name="quantity" x-model.number="qty" min="1" :max="max" value="1" aria-label="Jumlah"
                                                   class="w-10 border-0 bg-transparent p-0 text-center text-base font-semibold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                            <button type="button" @click="qty = Math.min(max, qty + 1)" class="grid size-10 place-items-center rounded-full transition hover:bg-sand-100" aria-label="Tambah"><x-icon name="plus" class="size-4" /></button>
                                        </div>
                                    </div>
                                    <p x-ref="variantHint" x-show="needsVariant" class="mt-4 hidden text-sm font-medium text-accent-600">Pilih {{ mb_strtolower($product->variant_label ?: 'varian') }} terlebih dahulu.</p>
                                </form>
                                {{-- Tombol di luar <form>: tombol wishlist punya form sendiri, dan form bersarang membuat "Beli sekarang" tidak terkirim. --}}
                                <div class="mt-5 flex gap-2">
                                    <button form="add-to-cart" class="btn btn-brand btn-pill min-h-14 flex-1 text-[15px]"><x-icon name="bag" class="size-5" /> {{ $preorder ? 'Pre Order ke keranjang' : 'Tambah ke keranjang' }}</button>
                                    <x-wishlist-button :product="$product" variant="outline" class="shrink-0" />
                                </div>
                                <button form="add-to-cart" name="buy_now" value="1" class="btn btn-ink btn-pill mt-2 min-h-14 w-full text-[15px]">{{ $preorder ? 'Pre Order sekarang' : 'Beli sekarang' }}</button>
                            @else
                                <div class="rounded-2xl bg-sand-100 p-5 text-sm text-stone-600">
                                    Produk ini sedang habis.
                                    @if (config('shop.whatsapp'))
                                        <a href="https://wa.me/{{ config('shop.whatsapp') }}?text={{ urlencode('Halo, kapan '.$product->name.' tersedia lagi?') }}" target="_blank" rel="noopener" class="link-underline font-semibold text-ink">Tanya ketersediaan</a>
                                    @endif
                                </div>
                                <x-wishlist-button :product="$product" variant="button" class="mt-3" />
                            @endif
                        </div>

                        <ul class="mt-6 divide-y divide-stone-100 rounded-2xl border border-stone-200 text-sm">
                            @foreach ([['shield', 'Produk resmi '.config('shop.name'), 'Original dan bergaransi'], ['truck', 'Kirim se-Indonesia', 'Ongkir dihitung otomatis saat checkout'], ['refresh', 'Tukar 7 hari', 'Bila ada cacat produksi']] as [$icon, $label, $note])
                                <li class="flex items-center gap-3 px-4 py-3">
                                    <x-icon :name="$icon" class="size-5 shrink-0 text-brand-600" />
                                    <span class="min-w-0"><span class="font-medium text-ink">{{ $label }}</span> <span class="text-stone-500">· {{ $note }}</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tab informasi --}}
        <section class="shop-container mt-16 lg:mt-24" x-data="{ tab: @js(array_key_first($tabs)) }">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
                <div class="min-w-0 lg:col-span-8">
                    <div class="no-scrollbar -mx-4 flex gap-7 overflow-x-auto border-b border-stone-200 px-4 sm:mx-0 sm:px-0" role="tablist" aria-label="Informasi produk">
                        @foreach ($tabs as $key => $label)
                            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" @click="tab = '{{ $key }}'"
                                    :aria-selected="tab === '{{ $key }}'" :class="tab === '{{ $key }}' ? 'text-ink after:bg-ink' : 'text-stone-500 hover:text-ink after:bg-transparent'"
                                    @class(['relative -mb-px min-h-12 shrink-0 text-sm font-semibold transition after:absolute after:inset-x-0 after:bottom-0 after:h-0.5', 'text-ink after:bg-ink' => $loop->first, 'text-stone-500' => ! $loop->first])>{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="mt-7 text-[15px] leading-relaxed text-stone-600">
                        @foreach ($tabs as $key => $label)
                            <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif>
                                @if ($key === 'desc')
                                    <div class="max-w-3xl whitespace-pre-line">{{ $product->description }}</div>
                                @elseif ($key === 'detail')
                                    <dl class="grid max-w-xl text-sm sm:grid-cols-[180px_1fr]">
                                        @php($row = 'border-b border-stone-100 py-3')
                                        @if ($product->sku)
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">SKU</dt><dd class="{{ $row }}"><span x-text="selected?.sku || @js($product->sku)">{{ $product->sku }}</span></dd>
                                        @endif
                                        @if ($product->category)
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">Kategori</dt><dd class="{{ $row }}">{{ $product->category->parent ? $product->category->parent->name.' › ' : '' }}{{ $product->category->name }}</dd>
                                        @endif
                                        @if ($product->collections->isNotEmpty())
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">Cocok untuk</dt>
                                            <dd class="{{ $row }} flex flex-wrap gap-1.5">
                                                @foreach ($product->collections as $collection)
                                                    <a href="{{ $collection->url() }}" class="rounded-full bg-sand-100 px-2.5 py-0.5 text-xs font-medium text-ink hover:bg-sand-200">{{ $collection->name }}</a>
                                                @endforeach
                                            </dd>
                                        @endif
                                        @if ($variants->isNotEmpty())
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">{{ $product->variant_label ?: 'Varian' }}</dt><dd class="{{ $row }}">{{ $variants->pluck('name')->join(', ') }}</dd>
                                        @endif
                                        @if ($tiers)
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">Harga grosir</dt>
                                            <dd class="{{ $row }}">{{ collect($tiers)->map(fn ($t) => 'min. '.$t['min_quantity'].' pcs '.\App\Support\Money::rupiah($t['price']))->join(' · ') }}</dd>
                                        @endif
                                        @if ($preorder)
                                            <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">Pre Order</dt><dd class="{{ $row }}">{{ $product->preorderSummary() }}</dd>
                                        @endif
                                        <dt class="{{ $row }} font-medium text-ink max-sm:border-0 max-sm:pb-0">Berat</dt><dd class="{{ $row }}">{{ $product->weight }} gr</dd>
                                    </dl>
                                @elseif ($key === 'ship')
                                    <div class="grid max-w-3xl gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl bg-sand-50 p-5"><x-icon name="truck" class="size-6 text-brand-600" /><p class="mt-3 font-semibold text-ink">Pengiriman</p><p class="mt-1 text-sm">Dikirim dari gudang kami dengan kurir pilihanmu (JNE, SiCepat, J&amp;T, POS, dan lainnya). Ongkir dihitung otomatis saat checkout.@if ($preorder) <strong class="font-semibold text-ink">Produk ini Pre Order: {{ mb_strtolower($product->preorderSummary()) }}.</strong>@endif</p></div>
                                        <div class="rounded-2xl bg-sand-50 p-5"><x-icon name="credit-card" class="size-6 text-brand-600" /><p class="mt-3 font-semibold text-ink">Pembayaran</p><p class="mt-1 text-sm">Bayar dengan transfer bank, e-wallet, atau QRIS. Pesanan diproses setelah pembayaran terkonfirmasi.</p></div>
                                    </div>
                                @else
                                    <div class="max-w-3xl rounded-2xl bg-sand-50 p-5"><x-icon name="refresh" class="size-6 text-brand-600" /><p class="mt-3 text-sm">Produk resmi Cheery Chic. Produk cacat produksi bisa ditukar dalam 7 hari setelah diterima, cukup hubungi CS kami dengan menyertakan nomor pesanan.</p></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="rounded-2xl border border-stone-200 p-6 lg:col-span-4 lg:self-start lg:p-7">
                    <span class="grid size-11 place-items-center rounded-full bg-sand-100 text-ink"><x-category-icon :for="$sportFor" class="size-[22px]" /></span>
                    <p class="mt-5 text-lg font-semibold tracking-tight text-ink">Bingung pilih ukuran atau bahan?</p>
                    <p class="mt-1.5 text-sm text-stone-500">Tim Cheery Chic siap bantu memilih motif dan ukuran yang pas untukmu.</p>
                    @if (config('shop.whatsapp'))
                        <a href="https://wa.me/{{ config('shop.whatsapp') }}?text={{ urlencode('Halo Cheery Chic, saya mau tanya tentang '.$product->name) }}" target="_blank" rel="noopener" class="btn btn-outline btn-pill mt-5"><x-icon name="chat" class="size-4" /> Chat WhatsApp</a>
                    @else
                        <a href="{{ route('articles.index') }}" class="btn btn-outline btn-pill mt-5">Baca tips & inspirasi <x-icon name="arrow-right" class="size-4" /></a>
                    @endif
                    <p class="kicker mt-6">{{ config('shop.hashtag') }}</p>
                </aside>
            </div>
        </section>

        <div class="shop-container">
            @include('shop.products.partials.reviews')
        </div>

        @if ($related->isNotEmpty())
            <section class="section-gap pb-28 lg:pb-0" aria-labelledby="related-title"
                     x-data="{ go(d) { const t = this.$refs.track; t.scrollBy({ left: d * t.clientWidth * 0.8, behavior: 'smooth' }) } }">
                <div class="shop-container flex items-end justify-between gap-4">
                    <div>
                        <p class="kicker">{{ $product->category?->name ?? 'Rekomendasi' }}</p>
                        <h2 id="related-title" class="section-title">Mungkin kamu suka</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($product->category)
                            <a href="{{ route('products.index', ['kategori' => $product->category->slug]) }}" class="link-arrow mr-1 sm:mr-3">Lihat semua <x-icon name="arrow-right" class="size-4" /></a>
                        @endif
                        <button type="button" @click="go(-1)" class="hidden size-11 place-items-center rounded-full border border-stone-200 transition hover:border-ink sm:grid" aria-label="Geser ke kiri"><x-icon name="arrow-left" class="size-[18px]" /></button>
                        <button type="button" @click="go(1)" class="hidden size-11 place-items-center rounded-full border border-stone-200 transition hover:border-ink sm:grid" aria-label="Geser ke kanan"><x-icon name="arrow-right" class="size-[18px]" /></button>
                    </div>
                </div>
                <div class="shop-container mt-7 sm:mt-9">
                    <div x-ref="track" class="no-scrollbar -mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:scroll-px-0 sm:gap-6 sm:px-0">
                        @foreach ($related as $item)
                            <x-product-card :product="$item" class="w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-[calc((100%-4.5rem)/4)]" />
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            <div class="pb-28 lg:pb-0"></div>
        @endif

        {{-- Bar beli di mobile --}}
        @if ($product->inStock())
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200/70 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md lg:hidden">
                <div class="flex items-center gap-2 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs text-stone-500">{{ $product->name }}</p>
                        <p class="text-base font-semibold">
                            @if ($priceRange)<span x-show="!selected" class="text-xs font-normal text-stone-500">Mulai </span>@endif<span x-text="rupiah(price)">@rupiah($product->price)</span>
                        </p>
                    </div>
                    <button form="add-to-cart" class="btn btn-brand rounded-full px-5 py-3"><x-icon name="bag" class="size-4" /> Tambah</button>
                    <button form="add-to-cart" name="buy_now" value="1" class="btn btn-ink rounded-full px-5 py-3">{{ $preorder ? 'Pre Order' : 'Beli' }}</button>
                </div>
            </div>
        @endif
    </div>
</x-layouts.shop>
