<x-layouts.admin title="Koleksi">
    <x-admin.page-header title="Koleksi" description="Kelompok seperti Travel & Umrah, Premium Velvet, atau Hadiah Spesial. Satu produk boleh masuk beberapa koleksi, terlepas dari kategorinya.">
        <a href="{{ route('admin.collections.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah koleksi</a>
    </x-admin.page-header>

    <div class="mb-6 flex gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
        <x-icon name="info" class="size-5 shrink-0" />
        <p><b>Kategori</b> = jenis barang (Daster & Dress, One Set), satu per produk. <b>Koleksi</b> = tema atau kebutuhan (Busui Friendly, Motif Bunga), boleh lebih dari satu.
            Koleksi yang aktif & "tampil di beranda" mengisi ubin <i>Belanja per koleksi</i>; bila belum ada, beranda memakai kategori.</p>
    </div>

    <div class="card overflow-hidden">
        <form method="GET" class="border-b border-stone-100 p-4">
            <div class="relative max-w-xs">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-stone-400" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari koleksi..." class="input pl-10">
            </div>
        </form>

        @if ($collections->isEmpty())
            <x-empty-state icon="swatch" :title="$search ? 'Koleksi tidak ditemukan' : 'Belum ada koleksi'" description="Buat koleksi pertama, misalnya Busui Friendly, lalu pilih daster yang nyaman untuk menyusui.">
                <a href="{{ route('admin.collections.create') }}" class="btn btn-primary">Tambah koleksi</a>
            </x-empty-state>
        @else
            <div class="relative overflow-x-auto">
                <table class="table-base">
                    <thead class="bg-stone-50/70"><tr><th>Koleksi</th><th>Produk</th><th>Urutan</th><th>Beranda</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($collections as $collection)
                            @php([$tone] = $collection->toneClasses() ?? ['bg-brand-50 text-brand-600'])
                            <tr class="hover:bg-stone-50/50">
                                <td>
                                    <div class="flex min-w-52 items-center gap-3">
                                        <div class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl {{ $tone }}">
                                            @if ($collection->imageUrl())
                                                <img src="{{ $collection->imageUrl() }}" alt="" class="size-full object-cover">
                                            @else
                                                <x-category-icon :name="$collection->iconName()" :for="$collection->name.' '.$collection->slug" class="size-5" />
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-semibold text-stone-900">{{ $collection->name }}</p>
                                            <p class="text-xs text-stone-500">/produk?koleksi={{ $collection->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-semibold text-stone-700">{{ $collection->products_count }}</td>
                                <td>{{ $collection->sort_order }}</td>
                                <td>
                                    @if ($collection->show_on_home)
                                        <span class="badge bg-brand-50 text-brand-700 ring-brand-600/20">Tampil</span>
                                    @else
                                        <span class="text-xs text-stone-400">Tidak</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.collections.toggle', $collection) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button title="Klik untuk {{ $collection->is_active ? 'sembunyikan' : 'tampilkan' }}">
                                            @if ($collection->is_active)
                                                <span class="badge bg-emerald-50 text-emerald-700 ring-emerald-600/20">Aktif</span>
                                            @else
                                                <span class="badge bg-stone-100 text-stone-600 ring-stone-500/20">Nonaktif</span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ $collection->url() }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Lihat di toko"><x-icon name="external" class="size-4" /><span class="sr-only">Lihat di toko</span></a>
                                        <a href="{{ route('admin.collections.edit', $collection) }}" class="btn btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /> <span class="sr-only sm:not-sr-only">Edit</span></a>
                                        <x-confirm-delete :action="route('admin.collections.destroy', $collection)" title="Hapus koleksi {{ $collection->name }}?" message="Produk di dalamnya tidak ikut terhapus." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$collections" label="koleksi" />
        @endif
    </div>
</x-layouts.admin>
