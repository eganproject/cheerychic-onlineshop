{{--
    Lookbook motif: pilih produk di kiri, foto tiap motifnya tampil besar di kanan.
    Ciri brand daster: satu model hadir dalam banyak motif. Nama motif diambil dari
    varian aktif bila jumlahnya sama dengan jumlah foto (urutan foto = urutan varian).
--}}
@use('App\Support\Storefront')
@if ($lookbook->isNotEmpty())
    @php
        $looks = $lookbook->map(function ($product) {
            $variants = $product->activeVariants->values();
            $images = $product->images->sortBy('sort_order')->values();

            return [
                'name' => $product->name,
                'url' => route('products.show', $product),
                'price' => \App\Support\Money::rupiah($product->price),
                'compare' => $product->isOnSale() ? \App\Support\Money::rupiah($product->compare_at_price) : null,
                'photos' => $images->map(fn ($img, $i) => [
                    'src' => $img->url(),
                    'label' => $variants->count() === $images->count() ? $variants[$i]->name : 'Foto '.($i + 1),
                ])->all(),
            ];
        })->values();
    @endphp
    <section class="shop-container section-gap" aria-labelledby="home-lookbook"
             x-data="{ p: 0, f: 0, looks: @js($looks), get look() { return this.looks[this.p] }, get photo() { return this.look.photos[this.f] ?? this.look.photos[0] } }">
        <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="min-w-0 lg:col-span-5">
                <p class="kicker">Lookbook</p>
                <h2 id="home-lookbook" class="section-title">Satu model, banyak <em>motif</em>.</h2>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-stone-600 sm:text-[15px]">Tiap koleksi hadir dalam beberapa motif. Ketuk modelnya, lalu intip motif favoritmu sebelum masuk keranjang.</p>

                <ul class="no-scrollbar -mx-4 mt-7 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0" role="tablist" aria-label="Pilih model">
                    <template x-for="(item, i) in looks" :key="i">
                        <li class="shrink-0">
                            <button type="button" role="tab" :aria-selected="p === i" @click="p = i; f = 0"
                                    class="group flex min-h-11 w-full items-center gap-3 rounded-full px-4 py-2 text-left text-sm font-semibold transition lg:rounded-2xl lg:px-4 lg:py-3"
                                    :class="p === i ? 'bg-ink text-white lg:bg-blush-50 lg:text-ink lg:ring-1 lg:ring-blush-200' : 'bg-sand-100 text-stone-600 hover:text-ink lg:bg-transparent lg:hover:bg-sand-50'">
                                <span class="hidden size-12 shrink-0 overflow-hidden rounded-xl bg-sand-100 lg:block"><img :src="item.photos[0].src" alt="" loading="lazy" class="size-full object-cover object-top"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate lg:text-base" x-text="item.name"></span>
                                    <span class="hidden text-xs font-medium text-stone-500 lg:block" x-text="item.photos.length + ' motif · mulai ' + item.price"></span>
                                </span>
                                <x-icon name="arrow-right" class="hidden size-4 shrink-0 transition lg:block" x-bind:class="p === i ? 'text-brand-600 translate-x-0.5' : 'text-stone-300'" />
                            </button>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="min-w-0 lg:col-span-7">
                <div class="grid grid-cols-[1fr_auto] gap-3 sm:gap-4">
                    <a :href="look.url" class="arch group relative block aspect-[4/5] overflow-hidden bg-sand-100">
                        <template x-for="(ph, i) in look.photos" :key="look.name + i">
                            <img :src="ph.src" :alt="look.name + ' motif ' + ph.label" loading="lazy" decoding="async"
                                 x-show="f === i" x-transition.opacity.duration.400ms
                                 class="absolute inset-0 size-full object-cover object-top transition duration-700 group-hover:scale-[1.03]">
                        </template>
                        <span class="absolute inset-x-3 bottom-3 flex items-end justify-between gap-3 rounded-2xl bg-white/95 p-3 pl-4 shadow-lg backdrop-blur sm:inset-x-4 sm:bottom-4">
                            <span class="min-w-0">
                                <span class="block text-[10px] font-bold tracking-[0.16em] text-brand-700 uppercase" x-text="'Motif ' + photo.label"></span>
                                <span class="block truncate text-sm font-semibold text-ink sm:text-base" x-text="look.name"></span>
                                <span class="flex items-baseline gap-1.5 text-sm font-bold text-ink"><span x-text="look.price"></span><span class="text-xs font-medium text-stone-400 line-through" x-show="look.compare" x-text="look.compare"></span></span>
                            </span>
                            <span class="btn btn-brand btn-pill shrink-0 px-4 py-2.5 text-xs sm:text-sm">Lihat <x-icon name="arrow-right" class="size-4" /></span>
                        </span>
                    </a>
                    <ul class="flex w-16 flex-col gap-2 sm:w-20 sm:gap-3" aria-label="Pilih motif">
                        <template x-for="(ph, i) in look.photos" :key="look.name + 't' + i">
                            <li>
                                <button type="button" @click="f = i" :aria-label="'Motif ' + ph.label" :aria-pressed="f === i"
                                        class="block aspect-[3/4] w-full overflow-hidden rounded-2xl bg-sand-100 ring-2 ring-offset-2 transition"
                                        :class="f === i ? 'ring-brand-400' : 'ring-transparent opacity-70 hover:opacity-100'">
                                    <img :src="ph.src" alt="" decoding="async" class="size-full object-cover object-top">
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endif
