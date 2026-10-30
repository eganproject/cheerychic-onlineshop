@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => 'website', 'noindex' => false, 'bottomNav' => true, 'transparentHeader' => false, 'whatsappButton' => true, 'whatsappText' => null])
@php
    $user = auth()->user();
    $announcements = \App\Support\Storefront::announcements();
    $nav = [
        ['label' => 'Semua Produk', 'url' => route('products.index'), 'active' => request()->routeIs('products.index') && ! request()->hasAny(['kategori', 'koleksi', 'promo', 'urut', 'q'])],
        ['label' => 'Terbaru', 'url' => route('products.index', ['urut' => 'terbaru']), 'active' => request('urut') === 'terbaru'],
        ['label' => 'Best Seller', 'url' => route('products.best-sellers'), 'active' => request()->routeIs('products.best-sellers')],
        ['label' => 'Promo', 'url' => route('products.index', ['promo' => 1]), 'active' => request()->boolean('promo')],
        ['label' => 'Artikel', 'url' => route('articles.index'), 'active' => request()->routeIs('articles.*')],
    ];
    $accountUrl = $user ? $user->homeRoute() : route('login');
    $instagram = config('shop.instagram') ? ltrim(config('shop.instagram'), '@') : null;
    $whatsapp = config('shop.whatsapp');
    $hashtag = config('shop.hashtag', '#CeriaDiRumah');
    $hasMega = $navCategories->isNotEmpty() || $navCollections->isNotEmpty();

    // Header transparan (menyatu dengan hero gelap) saat halaman di paling atas; jadi putih solid saat di-scroll.
    $navLink = 'relative flex h-full items-center text-[13px] font-medium tracking-wide transition hover:text-ink group-data-[clear]/header:hover:text-white';
    $navIdle = 'text-stone-600 group-data-[clear]/header:text-white/80';
    $navActive = 'text-ink group-data-[clear]/header:text-white after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-brand-400';
    $iconBtn = 'relative grid size-11 place-items-center rounded-full transition hover:bg-sand-100 group-data-[clear]/header:hover:bg-white/10 lg:size-10';
@endphp
<x-layouts.base :title="$title" :description="$description" :image="$image" :canonical="$canonical" :type="$type" :noindex="$noindex" :viewport-fit="true" body-class="bg-white text-ink">
    @isset($head)
        <x-slot:head>{{ $head }}</x-slot:head>
    @endisset
    @isset($scripts)
        <x-slot:scripts>{{ $scripts }}</x-slot:scripts>
    @endisset

    <div x-data="{ menu: false, search: false, cats: false }"
         x-effect="document.documentElement.classList.toggle('overflow-hidden', menu || search)"
         @keydown.escape.window="menu = false; search = false; cats = false"
         class="flex min-h-screen flex-col">

        {{-- Bar pengumuman (diatur di Admin › Tampilan Beranda) --}}
        <div class="bg-ink text-white">
            <div class="shop-container grid h-9 grid-cols-[auto_1fr_auto] items-center gap-3 text-[11px] sm:text-xs">
                <div class="hidden items-center gap-5 text-white/60 lg:flex">
                    <a href="{{ auth()->check() && ! $user->isAdmin() ? route('account.orders.index') : route('login') }}" class="flex items-center gap-1.5 transition hover:text-white">
                        <x-icon name="truck" class="size-3.5" /> Lacak pesanan
                    </a>
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center gap-1.5 transition hover:text-white">
                            <x-icon name="chat" class="size-3.5" /> Bantuan
                        </a>
                    @endif
                </div>

                @if (count($announcements))
                    <div class="relative col-start-2 flex h-full min-w-0 items-center justify-center"
                         x-data="{ i: 0, n: {{ count($announcements) }}, t: null,
                                   go(d) { this.i = (this.i + d + this.n) % this.n },
                                   play() { if (this.n > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) this.t = setInterval(() => this.go(1), 5000) },
                                   stop() { clearInterval(this.t) } }"
                         x-init="play()" @mouseenter="stop()" @mouseleave="play()" aria-live="polite">
                        @if (count($announcements) > 1)
                            <button type="button" @click="go(-1)" class="absolute left-0 grid size-9 place-items-center text-white/50 hover:text-white lg:static lg:mr-2 lg:size-7" aria-label="Pengumuman sebelumnya">
                                <x-icon name="chevron-left" class="size-3.5" />
                            </button>
                        @endif
                        <div class="relative h-full min-w-0 flex-1 overflow-hidden lg:w-[34rem] lg:max-w-xl lg:flex-none">
                            @foreach ($announcements as $text)
                                <p x-show="i === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                                   x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                                   x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="-translate-y-full opacity-0"
                                   class="absolute inset-0 flex items-center justify-center truncate px-9 text-center font-medium tracking-wide text-white/90 lg:px-0">
                                    <span class="truncate">{{ $text }}</span>
                                </p>
                            @endforeach
                        </div>
                        @if (count($announcements) > 1)
                            <button type="button" @click="go(1)" class="absolute right-0 grid size-9 place-items-center text-white/50 hover:text-white lg:static lg:ml-2 lg:size-7" aria-label="Pengumuman berikutnya">
                                <x-icon name="chevron-right" class="size-3.5" />
                            </button>
                        @endif
                    </div>
                @endif

                <div class="col-start-3 hidden items-center gap-5 text-white/60 lg:flex">
                    @if ($instagram)
                        <a href="https://instagram.com/{{ $instagram }}" target="_blank" rel="noopener" class="transition hover:text-white">Instagram</a>
                    @endif
                    @guest
                        <a href="{{ route('login') }}" class="transition hover:text-white">Masuk</a>
                        <a href="{{ route('register') }}" class="font-semibold text-white transition hover:text-brand-300">Daftar &amp; dapat promo</a>
                    @else
                        <a href="{{ $accountUrl }}" class="transition hover:text-white">Hai, {{ \Illuminate\Support\Str::words($user->name, 1, '') }}</a>
                    @endguest
                </div>
            </div>
        </div>

        {{-- Header --}}
        <header @mouseleave="cats = false" @if ($transparentHeader) data-clear @endif
                x-data="{ top: true }" x-init="top = window.scrollY < 24" @scroll.window.throttle.50ms="top = window.scrollY < 24"
                x-bind:data-clear="@js($transparentHeader) && top && !cats ? '' : null"
                class="group/header sticky top-0 z-40 border-b border-stone-200/70 bg-white/95 text-ink backdrop-blur-md transition-[background-color,border-color,color] duration-300 data-[clear]:border-white/10 data-[clear]:bg-transparent data-[clear]:text-white data-[clear]:backdrop-blur-none">
            <div class="shop-container flex h-16 items-center gap-3 lg:h-[72px] lg:gap-10">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="shrink-0 py-1 lg:py-0" aria-label="{{ config('shop.full_name') }}">
                    <x-logo adaptive class="h-9 lg:h-11" />
                </a>

                {{-- Mobile: kolom cari (menu utama ada di navigasi bawah) --}}
                <button type="button" @click="search = true"
                        class="flex h-11 min-w-0 flex-1 items-center gap-2.5 rounded-full bg-sand-100 px-4 text-left text-sm text-stone-500 transition active:bg-sand-200 group-data-[clear]/header:bg-white/10 group-data-[clear]/header:text-white/70 group-data-[clear]/header:ring-1 group-data-[clear]/header:ring-white/15 lg:hidden">
                    <x-icon name="search" class="size-[18px] shrink-0" />
                    <span class="truncate">{{ request('q') ?: 'Cari daster, one set...' }}</span>
                </button>

                {{-- Navigasi utama (desktop) --}}
                <nav class="hidden h-full flex-1 items-center gap-8 lg:flex" aria-label="Navigasi utama">
                    @if ($hasMega)
                        <button type="button" @click="cats = !cats" @mouseenter="cats = true" :aria-expanded="cats" aria-controls="mega-menu"
                                @class([$navLink, 'gap-1', $navActive => request()->hasAny(['kategori', 'koleksi']), $navIdle => ! request()->hasAny(['kategori', 'koleksi'])])>
                            Belanja <x-icon name="chevron-down" class="size-3.5 transition" x-bind:class="cats && 'rotate-180'" />
                        </button>
                    @endif
                    @foreach (array_slice($nav, 1) as $item)
                        @if ($item['label'] === 'Promo')
                            {{-- Promo dibuat menonjol: warna merah mawar (warna label diskon) + titik berdenyut --}}
                            <a href="{{ $item['url'] }}" @mouseenter="cats = false" @class([$navLink, 'gap-1.5 font-semibold text-accent-600 hover:!text-accent-700 group-data-[clear]/header:text-accent-300 group-data-[clear]/header:hover:!text-accent-200', 'after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-accent-500' => $item['active']])>
                                <span class="relative flex size-2" aria-hidden="true">
                                    <span class="absolute inline-flex size-full rounded-full bg-accent-400 opacity-75 motion-safe:animate-ping"></span>
                                    <span class="relative inline-flex size-2 rounded-full bg-accent-500"></span>
                                </span>
                                {{ $item['label'] }}
                            </a>
                        @else
                            <a href="{{ $item['url'] }}" @mouseenter="cats = false" @class([$navLink, $navActive => $item['active'], $navIdle => ! $item['active']])>{{ $item['label'] }}</a>
                        @endif
                    @endforeach
                </nav>

                {{-- Kanan --}}
                <div @class(['flex items-center gap-0.5 lg:gap-1', 'ml-auto' => ! $bottomNav, 'max-lg:hidden' => $bottomNav])>
                    <form action="{{ route('products.index') }}" method="GET" role="search" class="mr-2 hidden xl:block">
                        <label class="relative block">
                            <span class="sr-only">Cari produk</span>
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 z-10 size-4 -translate-y-1/2 text-stone-400 group-data-[clear]/header:text-white/60" />
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk"
                                   class="h-10 w-56 rounded-full border-0 bg-sand-100 pr-4 pl-10 text-sm placeholder:text-stone-400 transition focus:w-64 focus:bg-white focus:text-ink focus:ring-1 focus:ring-stone-300 group-data-[clear]/header:bg-white/10 group-data-[clear]/header:text-white group-data-[clear]/header:placeholder:text-white/55">
                        </label>
                    </form>
                    <button type="button" @click="search = true" class="{{ $iconBtn }} hidden lg:grid xl:hidden" aria-label="Cari">
                        <x-icon name="search" />
                    </button>

                    @auth
                        <div x-data="{ open: false }" class="relative hidden lg:block">
                            <button type="button" @click="open = !open" @click.outside="open = false" :aria-expanded="open" aria-label="Akun" class="{{ $iconBtn }}">
                                <x-icon name="user" />
                            </button>
                            <div x-show="open" x-cloak x-transition.origin.top.right
                                 class="absolute right-0 mt-2 w-60 rounded-2xl border border-stone-200 bg-white p-2 text-ink shadow-xl shadow-ink/10">
                                <div class="px-3 py-2">
                                    <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-stone-500">{{ $user->email }}</p>
                                </div>
                                <div class="my-1 h-px bg-stone-100"></div>
                                @php($menuItem = 'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-sand-100 hover:text-ink')
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="{{ $menuItem }}"><x-icon name="dashboard" class="size-4" /> Admin Panel</a>
                                @else
                                    <a href="{{ route('account.dashboard') }}" class="{{ $menuItem }}"><x-icon name="user" class="size-4" /> Akun Saya</a>
                                    <a href="{{ route('account.orders.index') }}" class="{{ $menuItem }}"><x-icon name="receipt" class="size-4" /> Pesanan Saya</a>
                                    <a href="{{ route('account.wishlist') }}" class="{{ $menuItem }}"><x-icon name="heart" class="size-4" /> Wishlist</a>
                                    <a href="{{ route('account.reviews.index') }}" class="{{ $menuItem }}"><x-icon name="star" class="size-4" /> Ulasan Saya</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-stone-700 hover:bg-rose-50 hover:text-rose-600"><x-icon name="logout" class="size-4" /> Keluar</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="{{ $iconBtn }} hidden lg:grid" aria-label="Masuk">
                            <x-icon name="user" />
                        </a>
                    @endauth

                    @unless ($user?->isAdmin())
                        <a href="{{ $user ? route('account.wishlist') : route('login') }}" class="{{ $iconBtn }}"
                           x-data="{ count: {{ $wishlistCount }} }" @wishlist-updated.window="count = $event.detail.count" aria-label="Wishlist">
                            <x-icon name="heart" />
                            <span x-show="count > 0" x-text="count > 99 ? '99+' : count" @if (! $wishlistCount) x-cloak @endif
                                  class="absolute top-0.5 right-0.5 grid min-w-[18px] place-items-center rounded-full bg-ink px-1 text-[10px] leading-[18px] font-semibold text-white group-data-[clear]/header:bg-white group-data-[clear]/header:text-ink">{{ $wishlistCount }}</span>
                        </a>
                    @endunless

                    <a href="{{ route('cart.index') }}" class="{{ $iconBtn }} -mr-1 lg:mr-0" aria-label="Keranjang ({{ $cartCount }})"
                       x-data="{ count: {{ $cartCount }} }" @cart-updated.window="count = $event.detail.count" :aria-label="`Keranjang (${count})`">
                        <x-icon name="bag" />
                        <span x-show="count > 0" x-text="count > 99 ? '99+' : count" @if (! $cartCount) x-cloak @endif
                              class="absolute top-0.5 right-0.5 grid min-w-[18px] place-items-center rounded-full bg-brand-400 px-1 text-[10px] leading-[18px] font-bold text-ink">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
                    </a>
                </div>
            </div>

            {{-- Mega menu kategori (desktop) --}}
            @if ($hasMega)
                <div id="mega-menu" x-show="cats" x-cloak @click.outside="cats = false"
                     x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute inset-x-0 top-full hidden border-b border-stone-200 bg-white text-ink shadow-xl shadow-ink/5 lg:block">
                    <div class="shop-container grid grid-cols-[1fr_280px] gap-12 py-9">
                        <div>
                            @if ($navCollections->isNotEmpty())
                                <p class="eyebrow text-stone-400">Koleksi pilihan</p>
                                <div class="mt-3 mb-8 flex flex-wrap gap-2">
                                    @foreach ($navCollections as $collection)
                                        <a href="{{ $collection->url() }}" class="chip py-1.5"><x-category-icon :name="$collection->iconName()" :for="$collection->name.' '.$collection->slug" class="size-4 text-stone-500" /> {{ $collection->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                                <p class="eyebrow text-stone-400">{{ $navCollections->isNotEmpty() ? 'Kategori' : 'Belanja per kategori' }}</p>
                                <a href="{{ route('products.index') }}" class="link-arrow text-xs">Lihat semua produk <x-icon name="arrow-right" class="size-3.5" /></a>
                            </div>
                            <ul class="mt-6 grid grid-cols-3 gap-x-10 gap-y-7 xl:grid-cols-4">
                                @foreach ($navCategories as $category)
                                    <li>
                                        <a href="{{ route('products.index', ['kategori' => $category->slug]) }}" class="group flex items-center gap-2.5">
                                            <x-category-icon :for="$category->name.' '.$category->slug" class="size-5 text-stone-400 transition group-hover:text-brand-600" />
                                            <span class="text-sm font-semibold">{{ $category->name }}</span>
                                        </a>
                                        @if ($category->children->isNotEmpty())
                                            <ul class="mt-2.5 space-y-2 pl-[1.875rem]">
                                                @foreach ($category->children as $child)
                                                    <li><a href="{{ route('products.index', ['kategori' => $child->slug]) }}" class="text-sm text-stone-500 transition hover:text-ink">{{ $child->name }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <a href="{{ $nav[3]['url'] }}" class="ornament-bg-light arch group relative flex min-h-60 flex-col justify-end overflow-hidden bg-blush-100 p-6 text-ink">
                            <x-logo variant="mark" class="absolute -top-3 -right-4 h-32 opacity-25" aria-hidden="true" />
                            <span class="relative kicker text-blush-700">Flash sale</span>
                            <span class="relative display mt-3 text-[1.7rem] leading-tight">Daster favorit, harga <em>ceria</em>.</span>
                            <span class="relative mt-5 inline-flex items-center gap-1.5 text-sm font-bold text-ink">Belanja promo <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
                        </a>
                    </div>
                </div>
            @endif
        </header>

        {{-- Panel pencarian --}}
        <div x-show="search" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Cari produk">
            <div x-show="search" x-transition.opacity @click="search = false" class="absolute inset-0 bg-ink/40 backdrop-blur-sm"></div>
            <div x-show="search"
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="-translate-y-full"
                 class="relative rounded-b-3xl bg-white">
                <div class="shop-container py-6 sm:py-10">
                    <div class="flex items-center justify-between">
                        <p class="kicker">Cari produk</p>
                        <button type="button" @click="search = false" class="icon-btn -mr-2 size-11" aria-label="Tutup pencarian">
                            <x-icon name="x" />
                        </button>
                    </div>
                    <form action="{{ route('products.index') }}" method="GET" class="mt-3 flex items-center gap-3 border-b border-ink pb-3">
                        <x-icon name="search" class="size-6 shrink-0 text-stone-400" />
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Daster busui, one set kulot, motif bunga..."
                               x-effect="if (search) $nextTick(() => $el.focus())"
                               class="w-full border-0 bg-transparent p-0 text-xl font-semibold tracking-tight placeholder:font-normal placeholder:text-stone-300 focus:ring-0 sm:text-3xl">
                        <button class="btn btn-ink btn-pill hidden shrink-0 sm:inline-flex">Cari</button>
                    </form>
                    @php($popular = $navCollections->isNotEmpty() ? $navCollections->take(8)->map(fn ($c) => [$c->url(), $c->name, $c->iconName(), $c->name.' '.$c->slug]) : $navCategories->take(8)->map(fn ($c) => [route('products.index', ['kategori' => $c->slug]), $c->name, null, $c->name.' '.$c->slug]))
                    @if ($popular->isNotEmpty())
                        <p class="eyebrow mt-7 text-stone-400">Koleksi populer</p>
                        <div class="no-scrollbar -mx-4 mt-3 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0">
                            @foreach ($popular as [$url, $label, $icon, $for])
                                <a href="{{ $url }}" class="chip"><x-category-icon :name="$icon" :for="$for" class="size-4 text-stone-500" /> {{ $label }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel "Jelajahi" (tab Kategori di navigasi bawah, mobile) --}}
        <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-labelledby="explore-title" id="explore-sheet">
            <div x-show="menu" x-transition.opacity @click="menu = false" class="absolute inset-0 bg-ink/40 backdrop-blur-sm"></div>
            <div x-show="menu"
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                 class="absolute inset-x-0 bottom-0 flex max-h-[88dvh] flex-col rounded-t-3xl bg-white shadow-2xl">
                <div class="flex shrink-0 items-center justify-between px-5 pt-3">
                    <span class="absolute top-2 left-1/2 h-1 w-10 -translate-x-1/2 rounded-full bg-stone-200" aria-hidden="true"></span>
                    <h2 id="explore-title" class="display mt-3 text-xl">Jelajahi</h2>
                    <button type="button" @click="menu = false" class="icon-btn -mr-2 mt-3 size-11" aria-label="Tutup">
                        <x-icon name="x" />
                    </button>
                </div>
                <div class="overflow-y-auto overscroll-contain px-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                    <ul class="mt-3 grid grid-cols-4 gap-2">
                        @foreach ([['Semua', 'cube', $nav[0]], ['Terbaru', 'sparkles', $nav[1]], ['Promo', 'tag', $nav[3]], ['Artikel', 'newspaper', $nav[4]]] as [$label, $icon, $item])
                            <li>
                                <a href="{{ $item['url'] }}" class="flex flex-col items-center gap-2 rounded-2xl py-2 text-center text-xs font-medium text-stone-700 active:bg-sand-50">
                                    <span @class(['grid size-12 place-items-center rounded-2xl', 'bg-ink text-white' => $item['active'], 'bg-accent-50 text-accent-600 ring-1 ring-accent-200' => ! $item['active'] && $label === 'Promo', 'bg-sand-100 text-ink' => ! $item['active'] && $label !== 'Promo'])><x-icon :name="$icon" class="size-[22px]" /></span>
                                    <span @class(['font-semibold text-accent-600' => $label === 'Promo'])>{{ $label }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if ($navCollections->isNotEmpty())
                        <p class="eyebrow mt-6 text-stone-400">Koleksi</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($navCollections as $collection)
                                <a href="{{ $collection->url() }}" @class(['chip', 'chip-active' => request('koleksi') === $collection->slug])>
                                    <x-category-icon :name="$collection->iconName()" :for="$collection->name.' '.$collection->slug" class="size-4 opacity-70" /> {{ $collection->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @if ($navCategories->isNotEmpty())
                        <p class="eyebrow mt-6 text-stone-400">Kategori</p>
                        <ul class="mt-1 divide-y divide-stone-100">
                            @foreach ($navCategories as $category)
                                @php($current = request('kategori') === $category->slug)
                                <li x-data="{ open: {{ $category->children->contains('slug', request('kategori')) ? 'true' : 'false' }} }">
                                    <div class="flex items-center">
                                        <a href="{{ route('products.index', ['kategori' => $category->slug]) }}" @if ($current) aria-current="page" @endif
                                           @class(['flex min-h-14 flex-1 items-center gap-3 py-2 text-[15px] font-medium', 'text-brand-700' => $current])>
                                            <x-category-icon :for="$category->name.' '.$category->slug" class="size-5 shrink-0 text-stone-400" />
                                            {{ $category->name }}
                                        </a>
                                        @if ($category->children->isNotEmpty())
                                            <button type="button" @click="open = !open" :aria-expanded="open" class="-mr-2 grid size-11 place-items-center text-stone-400" aria-label="Subkategori {{ $category->name }}">
                                                <x-icon name="chevron-down" class="size-4 transition" x-bind:class="open && 'rotate-180'" />
                                            </button>
                                        @else
                                            <x-icon name="chevron-right" class="size-4 shrink-0 text-stone-300" />
                                        @endif
                                    </div>
                                    @if ($category->children->isNotEmpty())
                                        <ul x-show="open" x-cloak x-transition.opacity class="pb-3 pl-8">
                                            @foreach ($category->children as $child)
                                                <li><a href="{{ route('products.index', ['kategori' => $child->slug]) }}" @class(['flex min-h-10 items-center text-sm text-stone-600', 'font-semibold text-brand-700' => request('kategori') === $child->slug])>{{ $child->name }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-5 rounded-2xl bg-sand-50 p-4">
                        @auth
                            <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                            <p class="truncate text-xs text-stone-500">{{ $user->email }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="btn btn-ink btn-pill col-span-2 py-3">Admin Panel</a>
                                @else
                                    <a href="{{ route('account.orders.index') }}" class="btn btn-ink btn-pill py-3"><x-icon name="receipt" class="size-4" /> Pesanan</a>
                                    <a href="{{ route('account.addresses.index') }}" class="btn btn-outline btn-pill bg-white py-3"><x-icon name="map-pin" class="size-4" /> Alamat</a>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-stone-600">Masuk untuk checkout, simpan wishlist, dan melacak pesananmu.</p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <a href="{{ route('login') }}" class="btn btn-ink btn-pill py-3">Masuk</a>
                                <a href="{{ route('register') }}" class="btn btn-outline btn-pill bg-white py-3">Daftar</a>
                            </div>
                        @endauth
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="mt-3 flex min-h-11 items-center justify-center gap-2 text-sm font-semibold text-ink">
                                <x-icon name="chat" class="size-4" /> Butuh bantuan? Chat WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <main @class(['flex-1', '-mt-16 lg:-mt-[72px]' => $transparentHeader])>
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="bottom-nav-spacer relative mt-20 bg-ink text-white lg:mt-28">
            <span class="scallop-up absolute inset-x-0 -top-3.5 text-ink" aria-hidden="true"></span>
            {{-- Ajakan komunitas (disembunyikan saat checkout supaya fokus membayar) --}}
            <div @class(['ornament-bg border-b border-white/10', 'hidden' => request()->routeIs('checkout.*')])>
                <div class="shop-container flex flex-col gap-8 py-14 lg:flex-row lg:items-end lg:justify-between lg:py-20">
                    <div class="max-w-2xl">
                        <p class="kicker text-white/55">{{ $hashtag }}</p>
                        <h2 class="display mt-4 text-3xl leading-[1.1] sm:text-5xl">Di rumah aja, tetap <em class="text-brand-300">cantik</em> &amp; ceria.</h2>
                        <p class="mt-4 max-w-lg text-sm leading-relaxed text-white/60 sm:text-base">Pamerkan gaya santaimu pakai tagar {{ $hashtag }}, siapa tahu kamu yang kami repost! Bingung pilih motif atau ukuran? Tim {{ config('shop.name') }} siap bantu lewat chat.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @if ($instagram)
                            <a href="https://instagram.com/{{ $instagram }}" target="_blank" rel="noopener" class="btn btn-brand btn-pill">Follow &#64;{{ $instagram }}</a>
                        @endif
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-glass btn-pill"><x-icon name="chat" class="size-4" /> Chat WhatsApp</a>
                        @endif
                        @if (! $instagram && ! $whatsapp)
                            <a href="{{ route('products.index') }}" class="btn btn-brand btn-pill">Mulai belanja <x-icon name="arrow-right" class="size-4" /></a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="shop-container py-14 lg:py-16">
                <div class="grid gap-12 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <a href="{{ route('home') }}" class="inline-block" aria-label="{{ config('shop.full_name') }}"><x-logo dark variant="stacked" class="h-24" /></a>
                        <p class="mt-5 max-w-xs text-sm leading-relaxed text-white/55">{{ config('shop.tagline') }}.</p>
                        <p class="mt-4 font-display text-lg text-brand-300 italic">{{ config('shop.motto') }}</p>
                        @if (config('shop.email') || $whatsapp)
                            <ul class="mt-6 space-y-2 text-sm text-white/65">
                                @if ($whatsapp)
                                    <li class="flex items-center gap-2"><x-icon name="phone" class="size-4 text-white/35" /> +{{ $whatsapp }}</li>
                                @endif
                                @if (config('shop.email'))
                                    <li class="flex items-center gap-2"><x-icon name="envelope" class="size-4 text-white/35" /> <a href="mailto:{{ config('shop.email') }}" class="hover:text-white">{{ config('shop.email') }}</a></li>
                                @endif
                            </ul>
                        @endif
                    </div>
                    @php($footLink = 'inline-flex min-h-11 items-center text-white/70 transition hover:text-white lg:min-h-0')
                    <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-8">
                        <div>
                            <h3 class="eyebrow text-white/35">Belanja</h3>
                            <ul class="mt-3 text-sm lg:mt-5 lg:space-y-3">
                                <li><a href="{{ route('products.index') }}" class="{{ $footLink }}">Semua produk</a></li>
                                @foreach ($navCategories->take(6) as $category)
                                    <li><a href="{{ route('products.index', ['kategori' => $category->slug]) }}" class="{{ $footLink }}">{{ $category->name }}</a></li>
                                @endforeach
                                <li><a href="{{ $nav[3]['url'] }}" class="{{ $footLink }}">Promo</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="eyebrow text-white/35">Bantuan</h3>
                            <ul class="mt-3 text-sm lg:mt-5 lg:space-y-3">
                                <li><a href="{{ $user?->isAdmin() ? $accountUrl : ($user ? route('account.orders.index') : route('login')) }}" class="{{ $footLink }}">Lacak pesanan</a></li>
                                <li><a href="{{ $accountUrl }}" class="{{ $footLink }}">Akun saya</a></li>
                                <li><a href="{{ route('cart.index') }}" class="{{ $footLink }}">Keranjang</a></li>
                                <li><a href="{{ route('articles.index') }}" class="{{ $footLink }}">Artikel &amp; inspirasi</a></li>
                                @if ($whatsapp)
                                    <li><a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="{{ $footLink }}">WhatsApp</a></li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <h3 class="eyebrow text-white/35">Kenapa {{ config('shop.name') }}</h3>
                            <ul class="mt-3 text-sm text-white/70 lg:mt-5 lg:space-y-3">
                                @foreach ([['sparkles', 'Rayon premium adem'], ['truck', 'Kirim se-Indonesia'], ['refresh', 'Garansi tukar 7 hari'], ['lock', 'Pembayaran aman']] as [$icon, $label])
                                    <li class="flex min-h-9 items-center gap-2.5 lg:min-h-0"><x-icon :name="$icon" class="size-4 text-brand-400" /> {{ $label }}</li>
                                @endforeach
                            </ul>
                            <div class="mt-6 flex gap-2">
                                @foreach (array_filter([
                                    'Instagram' => $instagram ? 'https://instagram.com/'.$instagram : null,
                                    'TikTok' => config('shop.tiktok') ? 'https://tiktok.com/@'.ltrim(config('shop.tiktok'), '@') : null,
                                    'Facebook' => config('shop.facebook') ? 'https://facebook.com/'.ltrim(config('shop.facebook'), '@') : null,
                                ]) as $label => $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full px-3 py-1.5 text-xs font-medium text-white/75 ring-1 ring-white/15 transition hover:text-white hover:ring-white/40">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="shop-container flex flex-col gap-3 py-5 text-xs text-white/40 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ date('Y') }} {{ config('shop.full_name') }}. Semua hak dilindungi.</p>
                    <ul class="flex flex-wrap gap-1.5">
                        @foreach (['Transfer bank', 'E-wallet', 'QRIS', 'Kartu kredit'] as $method)
                            <li class="rounded-md border border-white/10 px-2 py-1 font-medium text-white/55">{{ $method }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </footer>

        @if ($whatsappButton)
            <x-whatsapp-button :text="$whatsappText" />
        @endif

        @if ($bottomNav)
            <x-bottom-nav :cart-count="$cartCount" :wishlist-count="$wishlistCount" />
        @endif
    </div>
</x-layouts.base>
