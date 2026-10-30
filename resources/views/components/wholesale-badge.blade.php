{{--
    Label kecil "Harga grosir" untuk baris keranjang, checkout, dan pesanan.
    <x-wholesale-badge />                 →  "Harga grosir"
    <x-wholesale-badge :savings="12000" /> →  "Harga grosir · hemat Rp12.000"
--}}
@props(['savings' => null])
<span {{ $attributes->class('inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] leading-tight font-semibold whitespace-nowrap text-brand-700 ring-1 ring-brand-200') }}>
    <x-icon name="tag" class="size-3" /> {{ $savings ? 'Harga grosir · hemat '.\App\Support\Money::rupiah($savings) : 'Harga grosir' }}
</span>
