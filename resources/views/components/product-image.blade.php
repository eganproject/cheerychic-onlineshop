@props(['product', 'class' => 'aspect-square', 'eager' => false, 'bg' => 'bg-sand-100'])
<div {{ $attributes->merge(['class' => 'relative overflow-hidden '.$bg.' '.$class]) }}>
    {{-- Placeholder: tampil bila produk belum punya foto, atau foto gagal dimuat (gambar dihapus dari atas) --}}
    <div class="@container grid size-full place-items-center bg-sand-100 p-[8%]">
        <div class="flex w-full flex-col items-center gap-2 text-center">
            <span class="logo-mask aspect-[760/920] w-[30%] max-w-14 bg-brand-300" aria-hidden="true"></span>
            <span class="max-w-[12rem] text-[11px] font-medium text-stone-400 line-clamp-2 @max-[9rem]:hidden">{{ $product->name }}</span>
        </div>
    </div>
    @if ($url = $product->imageUrl())
        <img src="{{ $url }}" alt="{{ $product->name }}" @unless ($eager) loading="lazy" @endunless decoding="async" referrerpolicy="no-referrer"
             onerror="this.remove()"
             class="absolute inset-0 size-full object-cover transition duration-700 ease-out group-hover:scale-[1.03]">
    @endif
</div>
