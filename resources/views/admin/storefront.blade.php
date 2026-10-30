@php
    $hint = fn (string $field) => filled($defaults[$field] ?? null) ? 'Kosongkan untuk memakai teks bawaan: "'.$defaults[$field].'"' : null;
    $sHint = fn (string $field, ?string $extra = null) => trim((filled($sectionDefaults[$field] ?? null) ? 'Kosongkan untuk teks bawaan: "'.$sectionDefaults[$field].'". ' : '').$extra) ?: null;
@endphp
<x-layouts.admin title="Tampilan Beranda">
    <x-admin.page-header title="Tampilan Beranda" description="Atur isi beranda: foto & teks hero, pengumuman topbar, urutan seksi beserta teksnya, dan produk Best Seller.">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-secondary"><x-icon name="external" class="size-4" /> Lihat beranda</a>
    </x-admin.page-header>

    <nav class="no-scrollbar -mx-1 mb-6 flex gap-2 overflow-x-auto px-1" aria-label="Bagian halaman">
        @foreach (['#hero' => 'Hero & topbar', '#seksi' => 'Seksi & teks', '#best-seller' => 'Best Seller'] as $href => $label)
            <a href="{{ $href }}" class="shrink-0 rounded-full border border-stone-200 bg-white px-4 py-1.5 text-sm font-semibold text-stone-700 hover:border-brand-400 hover:text-brand-700">{{ $label }}</a>
        @endforeach
        @can('catalog.manage')
            <a href="{{ route('admin.collections.index') }}" class="shrink-0 rounded-full border border-dashed border-stone-300 px-4 py-1.5 text-sm font-semibold text-stone-600 hover:border-brand-400 hover:text-brand-700">Koleksi <x-icon name="arrow-right" class="inline size-3.5" /></a>
        @endcan
    </nav>

    <form id="hero" method="POST" action="{{ route('admin.storefront.update') }}" enctype="multipart/form-data" class="grid scroll-mt-24 gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            {{-- Foto hero --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="photo" /></span>
                    <div><h2 class="font-bold text-stone-900">Foto hero</h2><p class="text-xs text-stone-500">Tampil tegak di dalam bingkai lengkung, di samping judul. Tanpa foto, beranda memakai ilustrasi daster bawaan.</p></div>
                </div>
                <x-form.image-upload name="hero_image" label="Foto utama (desktop)" optional :current="$images['hero_image']"
                    remove-name="remove_hero_image" aspect="aspect-[4/5]" wrapper-class="max-w-64" :max-kb="5120"
                    hint="Foto tegak rasio 4:5, disarankan 1600×2000 px (min. 1200×1500 px). Objek utama di tengah."
                    tip="Foto hero di laptop/PC dan tablet. Tampil tegak 4:5 di dalam bingkai lengkung (puncak setengah lingkaran) di samping judul, jadi teks tidak menutupi foto."
                    :tips="['Pakai foto potret 4:5, ideal 1600×2000 px. Foto mendatar (landscape) akan terpotong banyak di kiri-kanan.', 'Taruh model/produk di tengah, sedikit ke kanan; titik fokus foto ada di ±70% lebar.', 'Sisakan ruang kosong di atas kepala model, karena sudut atas terpotong melengkung.', 'Pojok kiri atas tertutup stempel bulat dan bagian bawah oleh kartu “Paling dicari”: jangan taruh detail penting di sana.', 'Simpan JPG/WebP kualitas ±80% (idealnya di bawah 600 KB).']" />
                <x-form.image-upload name="hero_image_mobile" label="Foto versi HP" optional :current="$images['hero_image_mobile']"
                    remove-name="remove_hero_image_mobile" aspect="aspect-[5/6]" wrapper-class="max-w-56" :max-kb="4096"
                    hint="Rasio 5:6 (mis. 1080×1296 px). Tanpa ini, foto utama dipakai dan dipotong sedikit atas-bawahnya."
                    tip="Foto khusus layar HP (lebar di bawah 768 px). Di HP bingkai lengkung sedikit lebih pendek (5:6) dan tampil di atas judul."
                    :tips="['Opsional bila foto utama sudah 4:5 dengan objek di tengah; isi bila ingin komposisi berbeda (mis. lebih dekat ke wajah/detail).', 'Ukuran 1080×1296 px (5:6), objek di tengah dengan ruang di atas untuk lengkungan.']" />
                <x-form.select name="hero_text" label="Warna teks di atas foto" :search="false" :selected="$values['hero_text'] ?? 'light'"
                    :options="['light' => 'Putih (untuk foto gelap)', 'dark' => 'Gelap (untuk foto terang)']"
                    tip="Pengaturan untuk tata letak lama yang menaruh teks di atas foto. Pada beranda berbingkai lengkung, judul tampil di samping foto di atas latar krem, jadi pilihan ini tidak mengubah tampilan." />
            </section>

            {{-- Teks hero --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-accent-50 text-accent-600"><x-icon name="sparkles" /></span>
                    <div><h2 class="font-bold text-stone-900">Judul & tombol</h2><p class="text-xs text-stone-500">Teks yang tampil di atas foto hero.</p></div>
                </div>
                <x-form.input name="hero_eyebrow" label="Teks kecil di atas judul" :value="$values['hero_eyebrow']" maxlength="60" optional :hint="$hint('hero_eyebrow')"
                    tip="Label pendek berhuruf kapital di atas judul besar, untuk konteks atau momen, mis. “Koleksi Ramadan 2027”." />
                <x-form.input name="hero_title" label="Judul" :value="$values['hero_title']" maxlength="90" optional :hint="$hint('hero_title')"
                    tip="Kalimat paling besar di beranda, yang pertama dibaca pengunjung."
                    :tips="['Singkat & kuat: 3–7 kata supaya tidak lebih dari 3 baris di HP.', 'Fokus pada manfaat, mis. “Daster Adem, Hari Makin Ceria”.', 'Kata terakhir otomatis miring emas; tulis *kata* untuk memilih kata lain.']" />
                <x-form.textarea name="hero_subtitle" label="Deskripsi singkat" :value="$values['hero_subtitle']" rows="2" maxlength="200" optional :hint="$hint('hero_subtitle')"
                    tip="Satu-dua kalimat pendukung di bawah judul."
                    :tips="['Idealnya di bawah 120 huruf, supaya tombol tetap terlihat tanpa scroll di HP.']" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="hero_primary_label" label="Tombol utama" :value="$values['hero_primary_label']" maxlength="30" optional :hint="$hint('hero_primary_label')"
                        tip="Tombol navy yang paling menonjol. Isi dengan ajakan utama."
                        :tips="['Pakai kata kerja 1–3 kata, mis. “Belanja Sekarang”.', 'Tombol tampil bila teks dan link-nya sama-sama terisi (atau memakai bawaan).']" />
                    <x-form.input name="hero_primary_url" label="Link tombol utama" :value="$values['hero_primary_url']" maxlength="255" optional placeholder="/produk" :hint="$hint('hero_primary_url')"
                        tip="Halaman yang dibuka saat tombol diklik."
                        :tips="['Halaman toko: awali dengan /, mis. /produk, /produk?kategori=daster-dress, /produk?koleksi=busui-bumil-friendly.', 'Link luar (mis. WhatsApp, Shopee): tulis lengkap dengan https://.', 'Cara cepat: buka halamannya di toko, salin alamat dari browser, lalu hapus bagian domain.']" />
                    <x-form.input name="hero_secondary_label" label="Tombol kedua" :value="$values['hero_secondary_label']" maxlength="30" optional :hint="$hint('hero_secondary_label')"
                        tip="Tombol bergaris di samping tombol utama, untuk ajakan alternatif, mis. “Lihat Promo” atau “Best Seller”." />
                    <x-form.input name="hero_secondary_url" label="Link tombol kedua" :value="$values['hero_secondary_url']" maxlength="255" optional placeholder="/produk?promo=1" :hint="$hint('hero_secondary_url')"
                        tip="Sama seperti link tombol utama: awali / untuk halaman toko atau https:// untuk link luar." />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Topbar --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="megaphone" /></span>
                    <div><h2 class="font-bold text-stone-900">Pengumuman topbar</h2><p class="text-xs text-stone-500">Bergantian tiap beberapa detik di bar paling atas.</p></div>
                </div>
                <x-form.textarea name="announcements" label="Pengumuman" :value="$values['announcements']" rows="5" maxlength="1000" optional
                    hint="Satu pengumuman per baris, maks. 6. Kosongkan untuk memakai teks bawaan."
                    tip="Bar tipis navy paling atas di semua halaman toko. Tiap baris tampil bergantian beberapa detik."
                    :tips="['Singkat, di bawah ±50 huruf, supaya tidak terpotong di HP.', 'Taruh info paling menarik di baris pertama: gratis ongkir, promo, atau voucher.', 'Hapus pengumuman yang promonya sudah berakhir.']" />
            </section>

            <div class="flex gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                <x-icon name="info" class="size-5 shrink-0" />
                <p>Link diawali <code class="font-mono">/</code> untuk halaman toko, mis. <code class="font-mono">/produk?kategori=daster-dress</code>, atau <code class="font-mono">https://</code> untuk link luar.</p>
            </div>

            <div class="card sticky top-20 p-4">
                <button class="btn btn-primary w-full">Simpan tampilan</button>
            </div>
        </div>
    </form>

    {{-- Seksi beranda: urutan, tampil/sembunyi, dan teks tiap seksi. --}}
    <form id="seksi" method="POST" action="{{ route('admin.storefront.sections') }}" class="mt-6 grid scroll-mt-24 gap-6 lg:grid-cols-3"
          x-data="{
              items: @js(old('sections') ? collect($sections)->sortBy(fn ($s) => ($i = array_search($s['key'], old('sections'))) === false ? 99 : $i)->map(fn ($s) => [...$s, 'on' => in_array($s['key'], old('sections'))])->values()->all() : $sections),
              move(i, step) { const to = i + step; if (to < 0 || to >= this.items.length) return; const [it] = this.items.splice(i, 1); this.items.splice(to, 0, it); },
          }">
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="list-number" /></span>
                    <div><h2 class="font-bold text-stone-900">Teks seksi</h2><p class="text-xs text-stone-500">Judul & isi setiap seksi di bawah hero. Kosongkan untuk teks bawaan. Di judul, tulis <code class="font-mono">*kata*</code> supaya kata itu jadi miring emas.</p></div>
                </div>

                <details class="group rounded-xl border border-stone-200 p-4" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-stone-800">Pita slogan & Best Seller <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" /></summary>
                    <div class="mt-4 space-y-5">
                        <x-form.input name="ribbon_text" label="Teks pita berjalan" optional :value="$sectionText['ribbon_text']" :maxlength="$textLimits['ribbon_text']"
                            :hint="$sHint('ribbon_text', 'Pisahkan kata/kalimat dengan tanda •')"
                            tip="Pita emas berjalan berisi deret kata/nilai toko berhuruf kapital, dipisah bunga emas, sebagai penyela antarseksi beranda."
                            :tips="['Isi 3–5 frasa pendek (1–3 kata), mis. Adem • Tidak menerawang • Jahitan rapi.']" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="best_eyebrow" label="Teks kecil Best Seller" optional :value="$sectionText['best_eyebrow']" :maxlength="$textLimits['best_eyebrow']" :hint="$sHint('best_eyebrow')"
                                tip="Teks pendek huruf kapital dengan bunga emas di atas judul seksi. Gunanya memberi konteks singkat, mis. “Pilihan pelanggan”." />
                            <x-form.input name="best_title" label="Judul Best Seller" optional :value="$sectionText['best_title']" :maxlength="$textLimits['best_title']" :hint="$sHint('best_title')" />
                        </div>
                    </div>
                </details>

                <details class="group rounded-xl border border-stone-200 p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-stone-800">Belanja per koleksi <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" /></summary>
                    <div class="mt-4 space-y-5">
                        <x-form.select name="activities_source" label="Isi ubin" :search="false" :selected="$sectionText['activities_source']"
                            :options="\App\Services\Storefront\ActivityTiles::SOURCES"
                            hint="Ubin & urutannya diatur di menu Koleksi (atau Kategori). Maks. {{ \App\Services\Storefront\ActivityTiles::LIMIT }} ubin, yang pertama jadi ubin besar."
                            tip="Sumber ubin seksi “Belanja per koleksi”. Otomatis = koleksi yang dicentang “Tampil di beranda”; bila belum ada, dipakai kategori induk."
                            :tips="['Isi foto di tiap koleksi; tanpa foto, ubin memakai foto produk di dalamnya (atau ikon bila kosong).', 'Ubin bisa digeser menyamping; taruh koleksi terlaris di urutan awal.']" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="activities_eyebrow" label="Teks kecil" optional :value="$sectionText['activities_eyebrow']" :maxlength="$textLimits['activities_eyebrow']"
                                hint="Kosongkan: &quot;Koleksi&quot; untuk koleksi, &quot;Kategori&quot; untuk kategori."
                                tip="Teks pendek huruf kapital dengan bunga emas di atas judul seksi. Gunanya memberi konteks singkat, mis. “Pilihan pelanggan”." />
                            <x-form.input name="activities_title" label="Judul" optional :value="$sectionText['activities_title']" :maxlength="$textLimits['activities_title']" :hint="$sHint('activities_title')" />
                        </div>
                        <label class="flex items-start gap-3 text-sm text-stone-700">
                            <input type="hidden" name="hero_rail" value="0">
                            <input type="checkbox" name="hero_rail" value="1" @checked(old('hero_rail', $sectionText['hero_rail'])) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                            <span><span class="inline-flex items-center gap-1.5">Tampilkan pintasan "Pilih koleksimu" di bawah hero <x-tip title="Pintasan koleksi" text="Deretan tombol kecil di bawah tombol hero berisi koleksi yang sama dengan ubin “Belanja per koleksi”, supaya pengunjung langsung bisa memilih tanpa scroll."
                                :tips="['Matikan bila koleksi yang tampil di beranda belum lengkap isinya.']" /></span><span class="block text-xs text-stone-500">Deretan tombol koleksi di bawah tombol hero.</span></span>
                        </label>
                    </div>
                </details>

                <details class="group rounded-xl border border-stone-200 p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-stone-800">Panel diskon <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" /></summary>
                    <div class="mt-4 space-y-5">
                        <p class="text-xs text-stone-500">Tampil bila ada produk yang sedang diskon (harga coret), bersama 4 produk diskon terbaru.</p>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="promo_eyebrow" label="Teks kecil" optional :value="$sectionText['promo_eyebrow']" :maxlength="$textLimits['promo_eyebrow']" :hint="$sHint('promo_eyebrow')"
                                tip="Teks pendek huruf kapital dengan bunga emas di atas judul seksi. Gunanya memberi konteks singkat, mis. “Pilihan pelanggan”." />
                            <x-form.input name="promo_title" label="Judul" optional :value="$sectionText['promo_title']" :maxlength="$textLimits['promo_title']" :hint="$sHint('promo_title')" />
                        </div>
                        <x-form.textarea name="promo_text" label="Deskripsi" optional :value="$sectionText['promo_text']" rows="2" :maxlength="$textLimits['promo_text']" :hint="$sHint('promo_text')" />
                        <div class="grid gap-5 sm:grid-cols-3">
                            <x-form.input name="promo_badge" label="Stiker bulat" optional :value="$sectionText['promo_badge']" :maxlength="$textLimits['promo_badge']" :hint="$sHint('promo_badge')"
                                tip="Label kecil merah di samping teks kecil panel diskon, untuk menonjolkan angka promo."
                                :tips="['1–3 kata, mis. “Hemat 30%” atau “Hari ini”.']" />
                            <x-form.input name="promo_button" label="Tombol" optional :value="$sectionText['promo_button']" :maxlength="$textLimits['promo_button']" :hint="$sHint('promo_button')" />
                            <x-form.input name="promo_url" label="Link tombol" optional :value="$sectionText['promo_url']" :maxlength="$textLimits['promo_url']" placeholder="/produk?promo=1" :hint="$sHint('promo_url')"
                                tip="Halaman tujuan tombol panel diskon. /produk?promo=1 = katalog yang hanya berisi produk diskon." />
                        </div>
                    </div>
                </details>

                <details class="group rounded-xl border border-stone-200 p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-stone-800">Kenapa {{ config('shop.name') }} & artikel <x-icon name="chevron-down" class="size-4 transition group-open:rotate-180" /></summary>
                    <div class="mt-4 space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="why_eyebrow" label="Teks kecil" optional :value="$sectionText['why_eyebrow']" :maxlength="$textLimits['why_eyebrow']"
                                hint="Kosongkan: &quot;Kenapa {{ config('shop.name') }}&quot;."
                                tip="Teks pendek huruf kapital dengan bunga emas di atas judul seksi. Gunanya memberi konteks singkat, mis. “Pilihan pelanggan”." />
                            <x-form.input name="why_title" label="Judul besar" optional :value="$sectionText['why_title']" :maxlength="$textLimits['why_title']"
                                hint="Pisahkan baris dengan •. Baris ke-2 berwarna emas. Kosongkan untuk motto toko."
                                tip="Judul besar seksi keunggulan toko. Tanda • memecah judul jadi beberapa baris, dan baris kedua diberi warna aksen."
                                :tips="['Contoh: Adem di badan • ceria di hati', 'Maksimal 2–3 baris pendek agar tetap elegan.']" />
                        </div>
                        <x-form.textarea name="why_text" label="Paragraf" optional :value="$sectionText['why_text']" rows="2" :maxlength="$textLimits['why_text']" />
                        <x-form.textarea name="why_points" label="Poin keunggulan" optional :value="$sectionText['why_points']" rows="4" maxlength="1000"
                            hint="Satu poin per baris, format: Judul | deskripsi. Maks. 4 (ikon: truk, perisai, tukar, gembok). Kosongkan untuk poin bawaan."
                            tip="Daftar alasan berbelanja di toko ini, masing-masing dengan ikon bulat. Ikon mengikuti urutan baris: 1 truk, 2 perisai, 3 tukar, 4 gembok."
                            :tips="['Contoh baris: Gratis ongkir | Untuk belanja di atas Rp300.000', 'Urutkan sesuai ikonnya: pengiriman, jaminan kualitas, tukar ukuran/retur, pembayaran aman.', 'Deskripsi cukup satu kalimat pendek.']" />
                        <x-form.input name="articles_title" label="Judul seksi artikel" optional :value="$sectionText['articles_title']" :maxlength="$textLimits['articles_title']" :hint="$sHint('articles_title')" />
                    </div>
                </details>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-4 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="swatch" /></span>
                    <div><h2 class="font-bold text-stone-900">Urutan seksi</h2><p class="text-xs text-stone-500">Di bawah hero, dari atas ke bawah.</p></div>
                </div>
                <ol class="divide-y divide-stone-100 overflow-hidden rounded-xl border border-stone-200">
                    <li class="flex items-center gap-3 bg-stone-50 px-3 py-2.5 text-sm text-stone-500"><x-icon name="photo" class="size-4" /> Hero (selalu paling atas)</li>
                    <template x-for="(item, index) in items" :key="item.key">
                        <li class="flex items-center gap-2 bg-white px-3 py-2" :class="!item.on && 'bg-stone-50'">
                            <template x-if="item.on"><input type="hidden" name="sections[]" x-bind:value="item.key"></template>
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 text-sm">
                                <input type="checkbox" x-model="item.on" class="rounded text-brand-600 focus:ring-brand-500">
                                <span class="truncate font-medium" :class="item.on ? 'text-stone-900' : 'text-stone-400 line-through'" x-text="item.label"></span>
                            </label>
                            <button type="button" class="rounded p-1 text-stone-400 hover:text-stone-700 disabled:opacity-30" :disabled="index === 0" @click="move(index, -1)" title="Naikkan"><x-icon name="chevron-up" class="size-4" /><span class="sr-only">Naikkan</span></button>
                            <button type="button" class="rounded p-1 text-stone-400 hover:text-stone-700 disabled:opacity-30" :disabled="index === items.length - 1" @click="move(index, 1)" title="Turunkan"><x-icon name="chevron-down" class="size-4" /><span class="sr-only">Turunkan</span></button>
                        </li>
                    </template>
                </ol>
                <p class="hint">Hapus centang untuk menyembunyikan seksi. Seksi tanpa isi (mis. belum ada banner aktif) otomatis tidak tampil.</p>
                <x-input-error for="sections" />
            </section>

            <div class="card sticky top-20 p-4">
                <button class="btn btn-primary w-full">Simpan seksi</button>
            </div>
        </div>
    </form>

    {{-- Best Seller: form terpisah supaya tidak ikut mengunggah foto hero. --}}
    <form id="best-seller" method="POST" action="{{ route('admin.storefront.best-sellers') }}" class="mt-6 scroll-mt-24"
          x-data="bestSellerPicker(@js([
              'mode' => old('best_seller_mode', $bestSeller['mode']),
              'items' => $bestSeller['products'],
              'max' => \App\Services\Storefront\BestSellers::MAX_PICKS,
              'shown' => \App\Services\Storefront\BestSellers::LIMIT,
              'searchUrl' => route('admin.storefront.products'),
          ]))">
        @csrf
        @method('PUT')

        <section class="card space-y-5 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="trending-up" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-bold text-stone-900">Best Seller</h2>
                    <p class="text-xs text-stone-500">Produk di tab "Best Seller" beranda (tampil maks. {{ \App\Services\Storefront\BestSellers::LIMIT }}).</p>
                </div>
                <button class="btn btn-primary max-sm:w-full">Simpan Best Seller</button>
            </div>

            <fieldset class="grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">Cara memilih produk</legend>
                <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition" :class="mode === 'auto' ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-stone-200 hover:border-stone-300'">
                    <input type="radio" name="best_seller_mode" value="auto" x-model="mode" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                    <span><span class="block text-sm font-semibold text-stone-900">Otomatis</span>
                        <span class="block text-xs text-stone-500">Terlaris 90 hari terakhir, dilengkapi produk unggulan lalu produk terbaru.</span></span>
                </label>
                <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition" :class="mode === 'manual' ? 'border-brand-500 bg-brand-50/60 ring-1 ring-brand-500' : 'border-stone-200 hover:border-stone-300'">
                    <input type="radio" name="best_seller_mode" value="manual" x-model="mode" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                    <span><span class="block text-sm font-semibold text-stone-900">Manual</span>
                        <span class="block text-xs text-stone-500">Pilih & urutkan sendiri produknya.</span></span>
                </label>
            </fieldset>

            <div x-show="mode === 'manual'" x-cloak class="space-y-5">
                <label class="flex items-start gap-3 text-sm text-stone-700">
                    <input type="hidden" name="best_seller_fill" value="0">
                    <input type="checkbox" name="best_seller_fill" value="1" @checked(old('best_seller_fill', $bestSeller['fill'])) class="mt-0.5 rounded text-brand-600 focus:ring-brand-500">
                    <span>Lengkapi otomatis bila pilihan kurang dari {{ \App\Services\Storefront\BestSellers::LIMIT }}
                        <span class="block text-xs text-stone-500">Sisa tempat diisi produk terlaris (otomatis) setelah pilihan Anda.</span></span>
                </label>

                {{-- Pencarian produk --}}
                <div class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
                    <label for="best-seller-search" class="label">Tambah produk</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                        <input id="best-seller-search" type="search" x-model="query" autocomplete="off" class="input pl-9"
                               placeholder="Cari nama atau SKU produk..." :disabled="full"
                               @focus="search()" @input.debounce.300ms="search()" @keydown.enter.prevent="results[0] && add(results[0])">
                    </div>
                    <p class="hint" x-show="full">Sudah {{ \App\Services\Storefront\BestSellers::MAX_PICKS }} produk. Hapus salah satu untuk menambah.</p>
                    <div x-show="open && !full" x-transition.opacity class="absolute inset-x-0 top-full z-20 mt-1 max-h-80 overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-lg">
                        <p x-show="loading && results.length === 0" class="px-3 py-2 text-sm text-stone-500">Memuat...</p>
                        <p x-show="!loading && results.length === 0" class="px-3 py-2 text-sm text-stone-500">Produk tidak ditemukan.</p>
                        <template x-for="product in results" :key="product.id">
                            <button type="button" @click="add(product)" :disabled="has(product.id)"
                                    class="flex w-full items-center gap-3 rounded-lg px-2 py-1.5 text-left hover:bg-stone-50 disabled:cursor-default disabled:opacity-50">
                                <span class="size-10 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                                    <img x-show="product.image" :src="product.image" referrerpolicy="no-referrer" alt="" class="size-full object-cover" loading="lazy">
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-stone-900" x-text="product.name"></span>
                                    <span class="block truncate text-xs text-stone-500"><span x-text="product.price"></span> · Stok <span x-text="product.stock"></span><span x-show="product.sku"> · <span x-text="product.sku"></span></span></span>
                                </span>
                                <span x-show="problem(product)" x-text="problem(product)" class="badge bg-stone-100 text-stone-600"></span>
                                <span x-show="has(product.id)" class="text-brand-600"><x-icon name="check" class="size-4" /></span>
                                <span x-show="!has(product.id)" class="text-stone-400"><x-icon name="plus" class="size-4" /></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Daftar pilihan --}}
                <div>
                    <div class="mb-2 flex items-center justify-between text-xs text-stone-500">
                        <span>Seret <x-icon name="grip" class="inline size-4 align-text-bottom" /><span class="max-sm:hidden"> atau pakai tombol panah</span> untuk mengubah urutan.</span>
                        <span><span x-text="items.length"></span>/{{ \App\Services\Storefront\BestSellers::MAX_PICKS }}</span>
                    </div>
                    <p x-show="items.length === 0" class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">Belum ada produk. Cari produk di atas untuk menambahkan.</p>
                    <ol class="divide-y divide-stone-100 overflow-hidden rounded-xl border border-stone-200" x-show="items.length > 0">
                        <template x-for="(product, index) in items" :key="product.id">
                            <li :data-pick="index" class="flex items-center gap-2 bg-white px-2 py-2 sm:gap-3 sm:px-3"
                                :class="{ 'bg-brand-50 ring-2 ring-inset ring-brand-400': dragging === product.id, 'opacity-60': !visibleIds.includes(product.id) }">
                                <input type="hidden" name="best_seller_products[]" x-bind:value="product.id">
                                <button type="button" class="cursor-grab touch-none rounded p-1 text-stone-400 hover:text-stone-600 active:cursor-grabbing" title="Seret untuk mengubah urutan"
                                        @pointerdown.prevent="startDrag(index, $event)" @pointermove="drag($event)" @pointerup="endDrag()" @pointercancel="endDrag()">
                                    <x-icon name="grip" class="size-5" /><span class="sr-only">Seret</span>
                                </button>
                                <span class="w-5 text-center text-xs font-semibold text-stone-400" x-text="index + 1"></span>
                                <span class="size-10 shrink-0 sm:size-11 overflow-hidden rounded-lg bg-stone-100">
                                    <img x-show="product.image" :src="product.image" referrerpolicy="no-referrer" alt="" class="size-full object-cover" loading="lazy">
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-stone-900" x-text="product.name"></span>
                                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-stone-500">
                                        <span><span x-text="product.price"></span> · Stok <span x-text="product.stock"></span></span>
                                        <span x-show="problem(product)" class="badge whitespace-nowrap bg-amber-50 text-amber-700" title="Tidak tampil di beranda sampai produk aktif & ada stok" x-text="problem(product)"></span>
                                        <span x-show="!problem(product) && !visibleIds.includes(product.id)" class="badge whitespace-nowrap bg-stone-100 text-stone-600" title="Tampil bila ada pilihan di atasnya yang habis">Cadangan</span>
                                    </span>
                                </span>
                                <span class="flex shrink-0 items-center">
                                    <button type="button" class="rounded p-1 text-stone-400 hover:text-stone-700 disabled:opacity-30 max-sm:hidden" :disabled="index === 0" @click="move(index, -1)" title="Naikkan"><x-icon name="chevron-up" class="size-4" /><span class="sr-only">Naikkan</span></button>
                                    <button type="button" class="rounded p-1 text-stone-400 hover:text-stone-700 disabled:opacity-30 max-sm:hidden" :disabled="index === items.length - 1" @click="move(index, 1)" title="Turunkan"><x-icon name="chevron-down" class="size-4" /><span class="sr-only">Turunkan</span></button>
                                    <button type="button" class="rounded p-1 text-stone-400 hover:text-rose-600" @click="remove(index)" title="Hapus"><x-icon name="x" class="size-4" /><span class="sr-only">Hapus</span></button>
                                </span>
                            </li>
                        </template>
                    </ol>
                    <p class="hint">Produk nonaktif atau stok habis otomatis disembunyikan dari beranda, lalu tampil lagi saat stok kembali. Lebih dari {{ \App\Services\Storefront\BestSellers::LIMIT }} pilihan jadi cadangan.</p>
                    <x-input-error for="best_seller_products" />
                </div>
            </div>

            <p x-show="mode === 'auto' && items.length > 0" x-cloak class="text-xs text-stone-500">
                Daftar manual (<span x-text="items.length"></span> produk) tetap tersimpan dan dipakai lagi bila beralih ke Manual.
            </p>
        </section>
    </form>
</x-layouts.admin>
