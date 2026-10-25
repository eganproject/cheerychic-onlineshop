{{--
    Ikon garis kategori/koleksi Cheery Chic (24x24). Pilih lewat `name`, atau
    berikan `for` (nama/slug kategori) supaya ikon ditebak dari kata kuncinya.
    Contoh: <x-category-icon for="One Set Kulot" class="size-8" />
--}}
@props(['name' => null, 'for' => null, 'class' => 'size-6', 'stroke' => 1.5])
@php
    $paths = [
        // Daster: lengan kimono, leher bulat, badan melebar ke bawah.
        'dress' => 'M9.5 3.5c.4 1.2 1.3 1.9 2.5 1.9s2.1-.7 2.5-1.9l3.5 1.2 2.75 4.3-2.6 1.55-1.4-1.5c.3 4.2.9 8 2 11.45H5.25c1.1-3.45 1.7-7.25 2-11.45l-1.4 1.5L3.25 9l2.75-4.3 3.5-1.2Z',
        // One set: atasan + kulot.
        'set' => 'M9.5 2.75c.4 1 1.3 1.6 2.5 1.6s2.1-.6 2.5-1.6l3.25 1 1.75 3.1-2.1 1.15-1.15-.9.25 3.15h-9l.25-3.15-1.15.9L4.5 6.85l1.75-3.1 3.25-1ZM7.75 13h8.5l1 8.25h-3.6L12 15.5l-1.65 5.75h-3.6l1-8.25Z',
        // Busui: daster dengan kancing depan.
        'busui' => 'M9.5 3.5c.4 1.2 1.3 1.9 2.5 1.9s2.1-.7 2.5-1.9l3.5 1.2 2.75 4.3-2.6 1.55-1.4-1.5c.3 4.2.9 8 2 11.45H5.25c1.1-3.45 1.7-7.25 2-11.45l-1.4 1.5L3.25 9l2.75-4.3 3.5-1.2ZM12 8h.01M12 11h.01M12 14h.01',
        // Kaftan: lengan kelelawar lebar.
        'kaftan' => 'M9.25 3.5c.45 1.25 1.4 2 2.75 2s2.3-.75 2.75-2l6.75 2.25v6.5l-4.25.5-.75-3.25c.1 4.2.6 7.85 1.5 11.25h-12c.9-3.4 1.4-7.05 1.5-11.25l-.75 3.25-4.25-.5v-6.5L9.25 3.5Z',
        'pajama' => 'M9 3h6l3.5 2-1.5 3-1.5-.75V12h-7V7.25L7 8 5.5 5 9 3Zm3 0v9m-4 1.5h8l.75 7.75h-3.5L12 16l-1.25 5.25h-3.5L8 13.5ZM18.5 3.5l.4 1 1 .4-1 .4-.4 1-.4-1-1-.4 1-.4.4-1Z',
        'jumbo' => 'M9.5 3.5c.4 1.2 1.3 1.9 2.5 1.9s2.1-.7 2.5-1.9l3.5 1.2 2.75 4.3-2.6 1.55-1.4-1.5c.3 4.2.9 8 2 11.45H5.25c1.1-3.45 1.7-7.25 2-11.45l-1.4 1.5L3.25 9l2.75-4.3 3.5-1.2ZM8.75 15h6.5m-6.5 0 1.25-1.25M8.75 15 10 16.25M15.25 15 14 13.75M15.25 15 14 16.25',
        'batik' => 'M12 3.75a3 3 0 0 1 0 5.5 3 3 0 0 1 0-5.5Zm0 11a3 3 0 0 1 0 5.5 3 3 0 0 1 0-5.5ZM3.75 12a3 3 0 0 1 5.5 0 3 3 0 0 1-5.5 0Zm11 0a3 3 0 0 1 5.5 0 3 3 0 0 1-5.5 0ZM12 12h.01M3.75 3.75h.01M20.25 3.75h.01M3.75 20.25h.01M20.25 20.25h.01',
        'flower' => 'M12 14.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5ZM12 9.75C10.4 7.6 10.4 4.6 12 3c1.6 1.6 1.6 4.6 0 6.75Zm2.15 1.55c1.55-2.2 4.4-3.15 6.55-2.45-.35 2.2-2.95 3.95-5.6 3.95Zm-.8 2.55c2.6.8 4.45 3.2 4.35 5.45-2.2.35-4.6-1.5-5.2-4.15Zm-2.7 0c-.6 2.65-3 4.5-5.2 4.15-.1-2.25 1.75-4.65 4.35-5.45Zm-.8-2.55c-2.65 0-5.25-1.75-5.6-3.95 2.15-.7 5 .25 6.55 2.45Z',
        'kids' => 'M12 8.25a2.625 2.625 0 1 0 0-5.25 2.625 2.625 0 0 0 0 5.25ZM9.75 9.75h4.5l3 9H6.75l3-9ZM10.5 18.75V21M13.5 18.75V21',
        'gift' => 'M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 19.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21M2.625 11.25h18.75c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H2.625c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'moon' => 'M14.25 3.25A8.75 8.75 0 1 0 20.5 17.6 7.25 7.25 0 0 1 14.25 3.25ZM18 5l.55 1.45L20 7l-1.45.55L18 9l-.55-1.45L16 7l1.45-.55L18 5Z',
        'accessories' => 'M12 6.75a1.875 1.875 0 1 1 1.875-1.875c0 .875-.55 1.3-1.1 1.75-.45.375-.775.775-.775 1.375v.75m0 0-8.6 5.6c-1 .65-.55 2.15.65 2.15h15.9c1.2 0 1.65-1.5.65-2.15L12 8.75ZM6.75 19.5h10.5',
        'sparkles' => 'M9.8 15.9 9 18.75l-.8-2.85a4.5 4.5 0 0 0-3.1-3.1L2.25 12l2.85-.8a4.5 4.5 0 0 0 3.1-3.1L9 5.25l.8 2.85a4.5 4.5 0 0 0 3.1 3.1l2.85.8-2.85.8a4.5 4.5 0 0 0-3.1 3.1ZM18.26 8.72 18 9.75l-.26-1.03a3.38 3.38 0 0 0-2.46-2.46L14.25 6l1.03-.26a3.38 3.38 0 0 0 2.46-2.46L18 2.25l.26 1.03a3.38 3.38 0 0 0 2.46 2.46l1.03.26-1.03.26a3.38 3.38 0 0 0-2.46 2.46Z',
    ];

    if (! $name && $for) {
        $text = \Illuminate\Support\Str::lower($for);
        // Urutan penting: kata kunci yang lebih spesifik dicek lebih dulu.
        $keywords = [
            'gift' => ['hampers', 'gift', 'kado', 'hadiah', 'parcel', 'box'],
            'kids' => ['anak', 'kids', 'couple', 'balita', 'mom'],
            'busui' => ['busui', 'bumil', 'menyusui', 'hamil', 'kancing'],
            'jumbo' => ['jumbo', 'bigsize', 'big size', 'xxl', '3l', '4l', '5l'],
            'pajama' => ['piyama', 'pajama', 'tidur', 'sleep'],
            'set' => ['one set', 'oneset', 'setelan', 'set ', 'kulot', 'celana'],
            'kaftan' => ['kaftan', 'homedress', 'home dress', 'kimono', 'outer'],
            'batik' => ['batik', 'kawung', 'parang', 'etnik', 'tenun'],
            'moon' => ['ramadan', 'ramadhan', 'lebaran', 'idul', 'fitri'],
            'sparkles' => ['premium', 'rayon', 'eksklusif', 'baru', 'new'],
            'flower' => ['bunga', 'floral', 'motif', 'flower'],
            'accessories' => ['aksesoris', 'hijab', 'bergo', 'tas', 'pouch'],
            'dress' => ['daster', 'dress', 'gamis', 'santai', 'harian', 'rumah'],
        ];
        $name = collect($keywords)->search(fn ($words) => collect($words)->contains(fn ($w) => str_contains($text.' ', $w))) ?: 'dress';
    }
@endphp
<svg {{ $attributes->class($class) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="{{ $stroke }}" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $paths[$name] ?? $paths['dress'] }}" />
</svg>
