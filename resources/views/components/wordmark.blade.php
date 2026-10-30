{{-- Logo lengkap Cheery Chic untuk header/halaman publik. Lihat <x-logo>. --}}
@props(['dark' => false, 'size' => 'md'])
<x-logo :dark="$dark" {{ $attributes->class([
    'h-9 lg:h-10' => $size === 'md',
    'h-12 sm:h-14' => $size === 'lg',
]) }} />
