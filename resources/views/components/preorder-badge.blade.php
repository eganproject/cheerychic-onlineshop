{{--
    Label kecil "Pre Order" untuk baris keranjang, checkout, dan pesanan.
    <x-preorder-badge :days="14" />  →  "Pre Order · ±14 hari"
--}}
@props(['days' => null])
<span {{ $attributes->class('inline-flex items-center gap-1 rounded-full bg-sage-50 px-2 py-0.5 text-[11px] leading-tight font-semibold whitespace-nowrap text-sage-600 ring-1 ring-sage-200') }}>
    <x-icon name="clock" class="size-3" /> Pre Order{{ $days ? ' · ±'.$days.' hari' : '' }}
</span>
