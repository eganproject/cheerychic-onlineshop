@php
    $editing = $banner->exists;
@endphp
<x-layouts.admin :title="$editing ? 'Edit Banner' : 'Tambah Banner'">
    <x-admin.page-header :title="$editing ? 'Edit banner' : 'Tambah banner'" :back="route('admin.banners.index')" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-6">
                <x-form.image-upload name="image" label="Gambar banner" :current="$editing ? $banner->imageUrl() : null"
                    aspect="aspect-[21/9]" :max-kb="4096" :required="! $editing"
                    :hint="'Disarankan 1920×820 px (rasio ±21:9), JPG/PNG/WebP maks. 4 MB.'.($editing ? ' Kosongkan bila tidak diganti.' : '')"
                    tip="Gambar slider promo di beranda untuk layar tablet & laptop (tampil memanjang 21:9). Di HP, bila tidak ada gambar versi HP, gambar ini dipotong ke tengah jadi 4:3."
                    :tips="['Ukuran ideal 2400×1030 px atau minimal 1920×820 px.', 'Bila “Tampilkan teks” aktif, kosongkan sepertiga kiri karena judul & tombol tampil di sana. Taruh produk di kanan.', 'Bila tulisan promo sudah ada di gambar, taruh di tengah dan matikan “Tampilkan teks”.', 'Simpan JPG/WebP kualitas ±80% (idealnya di bawah 600 KB).']" />
                <x-form.image-upload name="mobile_image" label="Gambar versi HP" optional :current="$banner->mobileImageUrl()"
                    remove-name="remove_mobile_image" aspect="aspect-[4/5]" wrapper-class="max-w-56" :max-kb="4096"
                    hint="Rasio 4:5 (mis. 1080×1350 px). Tanpa ini, gambar utama dipotong otomatis di HP."
                    tip="Gambar khusus HP (layar di bawah 640 px), tampil tegak 4:5. Sangat disarankan agar banner tidak terpotong dan tulisan di gambar tetap terbaca."
                    :tips="['Ukuran 1080×1350 px (4:5).', 'Bila “Tampilkan teks” aktif, judul muncul di bagian bawah: taruh produk di separuh atas.', 'Tulisan di gambar dibuat besar, karena layar HP kecil.']" />
            </div>

            <div class="card space-y-5 p-6">
                <div>
                    <div class="label-row">
                        <label for="title" class="label">Judul</label>
                        <x-tip title="Judul" text="Teks besar di atas gambar (bila “Tampilkan teks” aktif). Selalu dipakai sebagai teks alternatif gambar untuk Google & pembaca layar."
                            :tips="['Singkat dan jelas promonya: 3–6 kata, mis. “Diskon 20% Daster Rayon”.']" />
                    </div>
                    <input id="title" name="title" value="{{ old('title', $banner->title) }}" required maxlength="120" placeholder="Diskon 10% daster rayon" class="input @error('title') input-error @enderror">
                    <p class="mt-1.5 text-xs text-stone-400">Juga dipakai sebagai teks alternatif gambar.</p>
                    <x-input-error for="title" />
                </div>
                <div>
                    <div class="label-row">
                        <label for="subtitle" class="label">Subjudul <span class="font-normal text-stone-400">(opsional)</span></label>
                        <x-tip title="Subjudul" text="Kalimat kecil di bawah judul, untuk detail promo seperti kode voucher atau periode. Hanya tampil bila “Tampilkan teks” aktif." />
                    </div>
                    <input id="subtitle" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" maxlength="255" placeholder="Pakai kode BERKAH10 sampai akhir bulan" class="input">
                    <x-input-error for="subtitle" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <div class="label-row">
                            <label for="link_url" class="label">Link <span class="font-normal text-stone-400">(opsional)</span></label>
                            <x-tip title="Link" text="Halaman yang dibuka saat banner diklik. Kosongkan bila banner hanya informasi."
                                :tips="['Halaman toko: awali dengan /, mis. /produk?promo=1 atau /produk?kategori=daster-dress.', 'Link luar: tulis lengkap dengan https:// (dibuka di tab baru).']" />
                        </div>
                        <input id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" maxlength="500" placeholder="/produk?promo=1" class="input @error('link_url') input-error @enderror">
                        <x-input-error for="link_url" />
                    </div>
                    <div>
                        <div class="label-row">
                            <label for="button_label" class="label">Teks tombol <span class="font-normal text-stone-400">(opsional)</span></label>
                            <x-tip title="Teks tombol" text="Tombol di bawah judul banner. Hanya tampil bila Link terisi dan “Tampilkan teks” aktif. Seluruh banner tetap bisa diklik walau tanpa tombol." />
                        </div>
                        <input id="button_label" name="button_label" value="{{ old('button_label', $banner->button_label) }}" maxlength="40" placeholder="Belanja sekarang" class="input">
                        <x-input-error for="button_label" />
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <label class="flex items-center justify-between gap-3">
                    <span><span class="block text-sm font-semibold text-stone-800">Tampilkan di beranda</span><span class="text-xs text-stone-500">Nonaktifkan untuk menyembunyikan</span></span>
                    <input type="checkbox" name="is_active" value="1" class="checkbox size-5" @checked(old('is_active', $banner->is_active))>
                </label>
                <label class="flex items-center justify-between gap-3">
                    <span><span class="flex items-center gap-1.5 text-sm font-semibold text-stone-800">Tampilkan teks di atas gambar <x-tip title="Teks di atas gambar" text="Bila aktif, judul, subjudul, dan tombol ditulis di atas gambar dengan lapisan gelap tipis supaya terbaca."
                        :tips="['Matikan bila desain banner sudah memuat tulisan, supaya teks tidak dobel.']" /></span><span class="text-xs text-stone-500">Matikan bila tulisan sudah ada di gambar</span></span>
                    <input type="checkbox" name="show_text" value="1" class="checkbox size-5" @checked(old('show_text', $banner->show_text))>
                </label>
                <x-form.number name="sort_order" label="Urutan tampil" :value="$banner->sort_order ?? 0" min="0"
                    tip="Urutan banner di slider: angka kecil tampil lebih dulu. Banner dengan angka sama diurutkan dari yang terbaru."
                    :tips="['Beri jarak 10, 20, 30 supaya mudah menyisipkan banner baru di tengah.']" />
                <x-form.date name="starts_at" label="Mulai tayang" optional time :value="$banner->starts_at"
                    tip="Banner otomatis muncul mulai waktu ini. Kosongkan untuk langsung tayang." />
                <x-form.date name="ends_at" label="Berakhir" optional time :value="$banner->ends_at"
                    tip="Banner otomatis hilang setelah waktu ini, jadi promo yang sudah selesai tidak perlu dimatikan manual. Kosongkan bila tanpa batas." />
            </div>
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Simpan perubahan' : 'Simpan banner' }}</button>
        </div>
    </form>
</x-layouts.admin>
