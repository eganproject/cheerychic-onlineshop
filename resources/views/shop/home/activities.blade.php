{{-- "Pilih gaya santaimu": ubin koleksi berbingkai daun, warna latar bergiliran ala kain daster, digeser bila tidak muat --}}
@use('App\Support\Storefront')
@if ($activities->isNotEmpty())
    @php($tones = ['bg-blush-100', 'bg-brand-100', 'bg-sage-100', 'bg-sand-200'])
    <section class="section-gap" aria-labelledby="home-categories">
        <div class="shop-container">
            <div class="section-head">
                <span class="ornament-mark" aria-hidden="true"><span class="flower-dot"></span></span>
                <p class="eyebrow mt-3 text-brand-700">{{ Storefront::text('activities_eyebrow', $activitiesKind === 'collections' ? 'Koleksi' : 'Kategori') }}</p>
                <h2 id="home-categories" class="section-title">{{ Storefront::emphasize(Storefront::text('activities_title', 'Pilih gaya santaimu')) }}</h2>
            </div>
            <ul class="no-scrollbar -mx-4 mt-8 grid snap-x auto-cols-[44%] grid-flow-col justify-center-safe gap-3 overflow-x-auto scroll-px-4 px-4 pb-2 sm:mx-0 sm:auto-cols-[28%] sm:gap-5 sm:scroll-px-0 sm:px-0 lg:mt-12 lg:auto-cols-[calc((100%-4*1.5rem)/5)] lg:gap-6">
                @foreach ($activities as $tile)
                    @php($photo = $tile['image'] ?? $tile['cover'] ?? null)
                    <li class="snap-start">
                        <a href="{{ $tile['url'] }}" class="group block">
                            <span @class(['arch relative grid aspect-[3/4] place-items-center overflow-hidden', $tones[$loop->index % count($tones)]])>
                                <x-category-icon :name="$tile['icon']" :for="$tile['for']" :stroke="1" class="size-14 text-brand-500 sm:size-16" />
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()"
                                         class="absolute inset-0 size-full object-cover object-top transition duration-700 group-hover:scale-[1.05]">
                                    <span class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-ink/70 to-transparent" aria-hidden="true"></span>
                                @endif
                                <span @class(['absolute inset-x-3 bottom-3 text-left sm:inset-x-4 sm:bottom-4', 'text-white' => $photo, 'text-ink' => ! $photo])>
                                    <span class="block font-display text-lg leading-tight font-semibold sm:text-xl">{{ $tile['name'] }}</span>
                                    <span @class(['mt-0.5 flex items-center gap-1 text-xs font-medium', 'text-white/80' => $photo, 'text-stone-500' => ! $photo])>{{ $tile['count'] }} produk <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" /></span>
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
                <li class="snap-start">
                    <a href="{{ route('products.index') }}" class="group block">
                        <span class="arch ornament-bg-light grid aspect-[3/4] place-items-center bg-blush-50 ring-1 ring-blush-200 ring-inset transition group-hover:bg-blush-100">
                            <span class="flex flex-col items-center gap-3 px-3 text-center">
                                <span class="grid size-12 place-items-center rounded-full bg-ink text-white transition group-hover:scale-105"><x-icon name="arrow-right" class="size-5" /></span>
                                <span class="font-display text-lg leading-tight font-semibold text-ink sm:text-xl">Semua produk</span>
                                <span class="text-xs text-stone-500">Jelajahi katalog</span>
                            </span>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </section>
@endif
