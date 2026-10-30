@php
    use Illuminate\Support\Arr;

    $heading = request('q') ? 'Hasil untuk "'.request('q').'"' : ($activeCategory?->name ?? $activeCollection?->name ?? (request()->boolean('promo') ? 'Promo' : 'Semua produk'));
    $baseQuery = request()->except('page');
    $pricePresets = [
        ['Di bawah 50rb', null, 50000],
        ['50rb – 100rb', 50000, 100000],
        ['100rb – 250rb', 100000, 250000],
        ['Di atas 250rb', 250000, null],
    ];

    // Filter aktif yang bisa dihapus satu per satu.
    $activeFilters = array_filter([
        'q' => request('q') ? 'Kata kunci: '.request('q') : null,
        'koleksi' => $activeCollection && ($activeCategory || request('q')) ? 'Koleksi: '.$activeCollection->name : null,
        'tersedia' => request()->boolean('tersedia') ? 'Tersedia' : null,
        'promo' => request()->boolean('promo') ? 'Sedang diskon' : null,
        'harga' => ($minPrice || $maxPrice)
            ? 'Harga: '.($minPrice ? \App\Support\Money::rupiah($minPrice) : 'Rp0').' – '.($maxPrice ? \App\Support\Money::rupiah($maxPrice) : '∞')
            : null,
    ]);
    $filterCount = count(array_diff_key($activeFilters, ['q' => true, 'koleksi' => true]));
    $collectionUrl = fn (?string $slug) => route('products.index', ($slug ? ['koleksi' => $slug] : []) + Arr::except($baseQuery, ['koleksi', 'kategori']));
    $hasPrice = $minPrice || $maxPrice;

    // Kategori induk yang sedang dibuka (untuk subkategori).
    $openParent = $activeCategory ? $categories->first(fn ($c) => $activeCategory->is($c) || (int) $activeCategory->parent_id === $c->id) : null;
    $categoryUrl = fn (?string $slug) => route('products.index', ($slug ? ['kategori' => $slug] : []) + Arr::except($baseQuery, 'kategori'));
    $toggleUrl = fn (string $key) => route('products.index', request()->boolean($key) ? Arr::except($baseQuery, $key) : [$key => 1] + $baseQuery);

    $context = $activeCategory ?? $activeCollection;
    $metaDescription = $context
        ? ($context->description ?: 'Belanja '.$context->name.' original di '.config('shop.full_name').'. Harga terjangkau, kirim ke seluruh Indonesia.')
        : null;
    $intro = $activeCategory?->description
        ?: ($activeCollection && ! $activeCategory ? ($activeCollection->tagline ?: $activeCollection->description) : null)
        ?: (request('q') ? 'Produk yang cocok dengan pencarianmu.' : (request()->boolean('promo') ? 'Harga spesial untuk daster favorit, selama stok masih ada.' : 'Daster, dress, dan one set rayon premium bermotif ceria. Adem dipakai seharian, cantik di rumah maupun di luar.'));
@endphp
<x-layouts.shop :title="$context?->name ?? 'Semua Produk'" :description="$metaDescription">
    @php
        $sheetTone = 'flex min-h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 px-3 text-center text-sm font-medium transition peer-checked:border-ink peer-checked:bg-ink peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 hover:border-stone-400';
        $bubble = 'relative grid size-16 shrink-0 place-items-center overflow-hidden rounded-full bg-white transition sm:size-20 lg:size-[88px]';
        $bubbleOn = 'ring-2 ring-ink ring-offset-2 ring-offset-blush-50';
        $bubbleOff = 'ring-1 ring-blush-200 group-hover:ring-brand-400';
    @endphp
    <div x-data="{ filters: false }" x-effect="document.body.classList.toggle('overflow-hidden', filters)" @keydown.escape.window="filters = false">
        {{-- Kepala katalog: latar blush bermotif bunga kain daster, judul rata tengah, tepi bawah renda --}}
        <section class="relative isolate overflow-hidden bg-blush-50">
            <div class="ornament-bg-light absolute inset-0 -z-10 [mask-image:linear-gradient(to_bottom,#000,transparent_85%)]" aria-hidden="true"></div>
            <div class="shop-container pt-5 pb-7 text-center lg:pt-8 lg:pb-10">
                <nav class="flex flex-wrap items-center justify-center gap-1.5 text-xs text-stone-500" aria-label="Breadcrumb">
                    <a href="{{ route('home') }}" class="hover:text-ink">Beranda</a>
                    <x-icon name="chevron-right" class="size-3 text-stone-300" />
                    @if ($activeCategory || $activeCollection)
                        <a href="{{ route('products.index') }}" class="hover:text-ink">Produk</a>
                        @if ($activeCollection)
                            <x-icon name="chevron-right" class="size-3 text-stone-300" />
                            @if ($activeCategory)
                                <a href="{{ $collectionUrl($activeCollection->slug) }}" class="hover:text-ink">{{ $activeCollection->name }}</a>
                            @else
                                <span class="text-ink">{{ $activeCollection->name }}</span>
                            @endif
                        @endif
                    @endif
                    @if ($activeCategory)
                        @if ($activeCategory->parent)
                            <x-icon name="chevron-right" class="size-3 text-stone-300" />
                            <a href="{{ $categoryUrl($activeCategory->parent->slug) }}" class="hover:text-ink">{{ $activeCategory->parent->name }}</a>
                        @endif
                        <x-icon name="chevron-right" class="size-3 text-stone-300" />
                        <span class="text-ink">{{ $activeCategory->name }}</span>
                    @elseif (! $activeCollection)
                        <span class="text-ink">Produk</span>
                    @endif
                </nav>

                <p class="eyebrow mt-6 text-brand-700 lg:mt-8">
                    @if ($activeCollection && $activeCategory)
                        {{ $activeCollection->name }}
                    @elseif ($activeCollection)
                        Koleksi
                    @elseif ($activeCategory?->parent)
                        {{ $activeCategory->parent->name }}
                    @elseif ($activeCategory)
                        Kategori
                    @else
                        Katalog {{ config('shop.name') }}
                    @endif
                </p>
                <h1 class="display mx-auto mt-2 max-w-3xl text-[2.25rem] leading-[1.05] break-words sm:text-5xl lg:text-[3.5rem]">{{ $heading }}</h1>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-stone-600 sm:text-[15px]">{{ $intro }}</p>

                {{-- Kategori: foto bulat (gambar kategori / foto produk terbaru di dalamnya) --}}
                @if ($categories->isNotEmpty())
                    <nav class="no-scrollbar -mx-4 mt-7 overflow-x-auto px-4 sm:mx-0 sm:px-0 lg:mt-9" aria-label="Kategori">
                        <ul class="mx-auto flex w-max gap-3 py-1 sm:gap-5">
                            <li>
                                <a href="{{ $categoryUrl(null) }}" class="group flex w-[76px] flex-col items-center gap-2 sm:w-24" @unless ($activeCategory) aria-current="page" @endunless>
                                    <span class="{{ $bubble }} {{ $activeCategory ? $bubbleOff : $bubbleOn }}">
                                        <x-logo variant="mark" class="h-auto w-[52%]" aria-hidden="true" />
                                    </span>
                                    <span @class(['text-xs leading-tight sm:text-[13px]', 'font-semibold text-ink' => ! $activeCategory, 'font-medium text-stone-600 group-hover:text-ink' => $activeCategory])>Semua</span>
                                </a>
                            </li>
                            @foreach ($categories as $category)
                                @php($on = (bool) $openParent?->is($category))
                                <li>
                                    <a href="{{ $categoryUrl($category->slug) }}" class="group flex w-[76px] flex-col items-center gap-2 sm:w-24" @if ($on) aria-current="page" @endif>
                                        <span class="{{ $bubble }} {{ $on ? $bubbleOn : $bubbleOff }}">
                                            <x-category-icon :for="$category->name.' '.$category->slug" class="size-7 text-brand-600 sm:size-8" />
                                            @if ($cover = $categoryCovers[$category->id] ?? null)
                                                <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()"
                                                     class="absolute inset-0 size-full object-cover object-top transition duration-500 group-hover:scale-105">
                                            @endif
                                        </span>
                                        <span @class(['text-xs leading-tight line-clamp-2 sm:text-[13px]', 'font-semibold text-ink' => $on, 'font-medium text-stone-600 group-hover:text-ink' => ! $on])>
                                            {{ $category->name }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                {{-- Subkategori dari kategori induk yang sedang dibuka --}}
                @if ($openParent?->children->isNotEmpty())
                    <div class="no-scrollbar -mx-4 mt-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                        <div class="mx-auto flex w-max items-center gap-2">
                            <a href="{{ $categoryUrl($openParent->slug) }}"
                               @class(['chip px-3.5 py-1.5 text-xs max-lg:min-h-9', 'chip-active' => $activeCategory->is($openParent)])>Semua {{ $openParent->name }}</a>
                            @foreach ($openParent->children as $child)
                                <a href="{{ $categoryUrl($child->slug) }}"
                                   @class(['chip px-3.5 py-1.5 text-xs max-lg:min-h-9', 'chip-active' => $activeCategory->is($child)])>{{ $child->name }} <span class="opacity-50">{{ $child->products_count }}</span></a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
        <div class="scallop -mt-px text-blush-50" aria-hidden="true"></div>

        {{-- Bar alat (lengket di bawah navbar): filter, filter cepat, jumlah, urutan --}}
        <div class="sticky top-16 z-30 border-b border-stone-200/80 bg-white/95 backdrop-blur-md lg:top-[72px]">
            <div class="shop-container flex h-[60px] items-center gap-2">
                <button type="button" @click="filters = true" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-full bg-ink pr-4 pl-3.5 text-sm font-medium text-white transition hover:bg-navy-800" aria-haspopup="dialog">
                    <x-icon name="filter" class="size-4" />
                    <span>Filter<span class="hidden sm:inline"> &amp; urutkan</span></span>
                    @if ($filterCount + ($activeCollection ? 1 : 0))
                        <span class="grid size-5 place-items-center rounded-full bg-brand-400 text-[10px] font-semibold text-ink">{{ $filterCount + ($activeCollection ? 1 : 0) }}</span>
                    @endif
                </button>

                <div class="hidden items-center gap-2 md:flex">
                    <a href="{{ $toggleUrl('tersedia') }}" @class(['chip h-10 px-3.5 py-0', 'chip-active' => request()->boolean('tersedia')]) @if (request()->boolean('tersedia')) aria-pressed="true" @endif>
                        <x-icon name="check" class="size-4" /> Tersedia
                    </a>
                    <a href="{{ $toggleUrl('promo') }}" @class(['chip h-10 px-3.5 py-0', 'chip-active' => request()->boolean('promo')]) @if (request()->boolean('promo')) aria-pressed="true" @endif>
                        <x-icon name="tag" class="size-4" /> Diskon
                    </a>
                </div>

                <p class="ml-auto hidden text-sm whitespace-nowrap text-stone-500 sm:block">
                    <span class="font-semibold text-ink">{{ $products->total() }}</span> produk
                </p>

                <form method="GET" x-data @change="$el.submit()" class="ml-auto flex min-w-0 items-center sm:ml-3">
                    @foreach (Arr::except($baseQuery, 'urut') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="sort" class="mr-2 hidden text-sm text-stone-500 lg:block">Urutkan</label>
                    <select id="sort" name="urut" class="h-10 min-w-0 rounded-full border-stone-200 bg-white py-0 pr-9 pl-4 text-base font-medium focus:border-ink focus:ring-ink/10 lg:text-sm">
                        @foreach ($sorts as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <div class="shop-container pt-6 pb-4 lg:pt-10">
            @if ($activeFilters || $activeCollection)
                <div class="mb-7 flex flex-wrap items-center gap-2">
                    @if ($activeCollection && ! isset($activeFilters['koleksi']))
                        <a href="{{ $collectionUrl(null) }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-medium text-ink ring-1 ring-brand-200 hover:bg-brand-100">
                            Koleksi: {{ $activeCollection->name }} <x-icon name="x" class="size-3.5" /><span class="sr-only">Hapus filter</span>
                        </a>
                    @endif
                    @foreach ($activeFilters as $key => $label)
                        @php($without = $key === 'harga' ? Arr::except($baseQuery, ['harga_min', 'harga_max']) : Arr::except($baseQuery, $key))
                        <a href="{{ route('products.index', $without) }}" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-medium text-ink ring-1 ring-brand-200 hover:bg-brand-100">
                            {{ $label }} <x-icon name="x" class="size-3.5" /><span class="sr-only">Hapus filter</span>
                        </a>
                    @endforeach
                    @if (count($activeFilters) + ($activeCollection && ! isset($activeFilters['koleksi']) ? 1 : 0) > 1)
                        <a href="{{ route('products.index', Arr::only($baseQuery, ['kategori'])) }}" class="link-underline px-1 text-xs font-semibold">Hapus semua</a>
                    @endif
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="rounded-3xl border border-dashed border-stone-300 bg-sand-50">
                    <x-empty-state icon="search" title="Produk tidak ditemukan" description="Coba kata kunci lain atau kurangi filter yang dipakai.">
                        <a href="{{ route('products.index') }}" class="btn btn-ink btn-pill">Lihat semua produk</a>
                    </x-empty-state>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-3 gap-y-9 sm:gap-x-5 md:grid-cols-3 lg:grid-cols-4 lg:gap-x-7 lg:gap-y-12">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" :eager="$loop->index < 4" />
                    @endforeach
                </div>
                <div class="mt-14">{{ $products->links() }}</div>
            @endif
        </div>

        {{-- Panel filter & urutan: lembar bawah di HP, laci kanan di desktop --}}
        <div x-show="filters" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="sheet-title">
            <div x-show="filters" x-transition.opacity @click="filters = false" class="absolute inset-0 bg-ink/40 backdrop-blur-sm"></div>
            <form x-show="filters"
                  x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="max-lg:translate-y-full lg:translate-x-full" x-transition:enter-end="max-lg:translate-y-0 lg:translate-x-0"
                  x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="max-lg:translate-y-0 lg:translate-x-0" x-transition:leave-end="max-lg:translate-y-full lg:translate-x-full"
                  method="GET" action="{{ route('products.index') }}"
                  @submit="[...$el.elements].forEach((field) => { if (field.name && ! field.value) field.disabled = true })"
                  x-data="{ min: @js(\App\Support\FormField::thousands($minPrice)), max: @js(\App\Support\FormField::thousands($maxPrice)) }"
                  class="absolute inset-x-0 bottom-0 flex max-h-[88svh] flex-col rounded-t-3xl bg-white lg:inset-y-0 lg:right-0 lg:left-auto lg:max-h-none lg:w-[420px] lg:rounded-none lg:rounded-l-3xl">
                @foreach (Arr::only($baseQuery, ['q', 'kategori']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div class="relative flex items-center justify-between px-5 pt-4 pb-3 lg:px-7 lg:pt-7">
                    <span class="absolute top-2 left-1/2 h-1 w-10 -translate-x-1/2 rounded-full bg-stone-200 lg:hidden" aria-hidden="true"></span>
                    <p id="sheet-title" class="display mt-2 text-2xl lg:mt-0 lg:text-3xl">Filter &amp; urutkan</p>
                    <button type="button" @click="filters = false" class="-mr-2 mt-2 grid size-10 place-items-center rounded-full hover:bg-sand-100 lg:mt-0" aria-label="Tutup filter"><x-icon name="x" /></button>
                </div>

                <div class="flex-1 space-y-7 overflow-y-auto overscroll-contain px-5 pt-2 pb-6 lg:px-7">
                    <div role="group" aria-labelledby="f-sort">
                        <p id="f-sort" class="eyebrow text-stone-500">Urutkan</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            @foreach ($sorts as $key => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" name="urut" value="{{ $key }}" class="peer sr-only" @checked($sort === $key)>
                                    <span class="{{ $sheetTone }}">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if ($collections->isNotEmpty())
                        <div role="group" aria-labelledby="f-collection">
                            <p id="f-collection" class="eyebrow text-stone-500">Koleksi</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="koleksi" value="" class="peer sr-only" @checked(! $activeCollection)>
                                    <span class="{{ $sheetTone }} rounded-full px-4">Semua</span>
                                </label>
                                @foreach ($collections as $item)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="koleksi" value="{{ $item->slug }}" class="peer sr-only" @checked($activeCollection?->is($item))>
                                        <span class="{{ $sheetTone }} rounded-full px-4">{{ $item->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div role="group" aria-labelledby="f-show">
                        <p id="f-show" class="eyebrow text-stone-500">Tampilkan</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="checkbox" name="tersedia" value="1" class="peer sr-only" @checked(request()->boolean('tersedia'))>
                                <span class="{{ $sheetTone }}"><x-icon name="check" class="size-4" /> Tersedia</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="promo" value="1" class="peer sr-only" @checked(request()->boolean('promo'))>
                                <span class="{{ $sheetTone }}"><x-icon name="tag" class="size-4" /> Sedang diskon</span>
                            </label>
                        </div>
                    </div>

                    <div role="group" aria-labelledby="f-price">
                        <p id="f-price" class="eyebrow text-stone-500">Harga</p>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            @foreach ($pricePresets as [$label, $from, $to])
                                <button type="button" @click="min = @js(\App\Support\FormField::thousands($from)); max = @js(\App\Support\FormField::thousands($to))"
                                        :class="min == @js(\App\Support\FormField::thousands($from)) && max == @js(\App\Support\FormField::thousands($to)) ? '!border-ink !bg-ink !text-white' : ''"
                                        class="min-h-11 rounded-xl border border-stone-200 px-3 text-sm font-medium transition hover:border-stone-400">{{ $label }}</button>
                            @endforeach
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <label class="sr-only" for="harga_min">Harga minimum</label>
                            <input id="harga_min" type="text" name="harga_min" x-model="min" x-money placeholder="Min Rp" class="input text-base lg:text-sm">
                            <label class="sr-only" for="harga_max">Harga maksimum</label>
                            <input id="harga_max" type="text" name="harga_max" x-model="max" x-money placeholder="Maks Rp" class="input text-base lg:text-sm">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-[auto_1fr] gap-2 border-t border-stone-200 px-5 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] lg:px-7 lg:py-5">
                    <a href="{{ route('products.index', Arr::only($baseQuery, ['q', 'kategori'])) }}" class="btn btn-outline rounded-full px-6 py-3">Reset</a>
                    <button class="btn btn-ink rounded-full py-3">Tampilkan produk</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.shop>
