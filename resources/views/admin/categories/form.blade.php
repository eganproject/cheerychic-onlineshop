@php($editing = $category->exists)
<x-layouts.admin :title="$editing ? 'Edit Kategori' : 'Tambah Kategori'">
    <x-admin.page-header :title="$editing ? 'Edit kategori' : 'Tambah kategori'" :back="route('admin.categories.index')" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-5 p-6 lg:col-span-2">
            <div>
                <label for="name" class="label">Nama kategori</label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" required class="input @error('name') input-error @enderror">
                <x-input-error for="name" />
            </div>
            <x-form.select name="parent_id" label="Induk kategori" placeholder="Tidak ada (kategori utama)" :search="true"
                :options="$parents->pluck('name', 'id')->all()" :selected="$category->parent_id"
                hint="Pilih induk untuk menjadikannya subkategori, mis. One Set › Setelan Celana Kulot."
                tip="Kategori maksimal 2 tingkat: kategori utama dan subkategori. Kategori utama tampil di menu toko, subkategori di dalamnya."
                :tips="['Cukup 4–6 kategori utama supaya menu tidak penuh.', 'Produk dipasang di subkategori; pembeli yang membuka kategori induk tetap melihatnya.']" />
            <div>
                <div class="label-row">
                    <label for="slug" class="label">Slug URL <span class="font-normal text-stone-400">(kosongkan untuk otomatis)</span></label>
                    <x-tip title="Slug URL" text="Nama kategori di alamat link, mis. /produk?kategori=daster-dress. Dibuat otomatis dari nama bila dikosongkan."
                        :tips="['Jangan diubah setelah link kategori dipakai di banner, hero, atau iklan, karena link lama akan kosong.']" />
                </div>
                <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="input @error('slug') input-error @enderror">
                <x-input-error for="slug" />
            </div>
            <x-form.textarea name="description" label="Deskripsi" optional :value="$category->description" rows="4" maxlength="1000"
                tip="Tampil sebagai paragraf pembuka di halaman katalog kategori ini, dan sebagai deskripsi halaman di Google."
                :tips="['1–2 kalimat (±150 huruf) yang menyebut jenis produk dan keunggulannya, mis. bahan & cocok untuk siapa.']" />
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <label class="flex items-center justify-between gap-3">
                    <span><span class="block text-sm font-semibold text-stone-800">Tampilkan di toko</span><span class="text-xs text-stone-500">Nonaktifkan untuk menyembunyikan</span></span>
                    <input type="checkbox" name="is_active" value="1" class="checkbox size-5" @checked(old('is_active', $category->is_active))>
                </label>
                <x-form.number name="sort_order" label="Urutan tampil" :value="$category->sort_order ?? 0" min="0"
                    tip="Urutan di menu toko, filter katalog & ubin beranda: angka kecil tampil lebih dulu. Angka sama diurutkan sesuai abjad."
                    :tips="['Pakai kelipatan 10 supaya mudah menyisipkan kategori baru.']" />
                <x-form.image-upload name="image" label="Gambar" optional :current="$category->imageUrl()" remove-name="remove_image" aspect="aspect-[3/4]" wrapper-class="max-w-48"
                    tip="Dipakai sebagai foto ubin “Belanja per koleksi” di beranda saat ubin diisi dari kategori (belum ada koleksi yang tampil di beranda). Tanpa gambar, ubin memakai foto produk di kategori ini."
                    :tips="['Foto tegak 3:4, minimal 900×1200 px. Ubin berbentuk lengkung (puncak setengah lingkaran) dengan nama di bawahnya.', 'Taruh objek di tengah dan sisakan ruang di atas, karena sudut atas terpotong melengkung.', 'Foto bernuansa senada (latar krem/netral) membuat deretan ubin terlihat rapi.', 'Kompres di bawah 400 KB agar beranda cepat.']" />
            </div>
            <button class="btn btn-primary w-full py-3">{{ $editing ? 'Simpan perubahan' : 'Simpan kategori' }}</button>
        </div>
    </form>
</x-layouts.admin>
