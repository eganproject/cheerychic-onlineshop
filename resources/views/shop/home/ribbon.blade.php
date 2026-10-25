{{-- Pita berjalan: keunggulan Cheery Chic di pita emas (teks diatur di Admin › Tampilan Beranda, dipisah "•") --}}
@php($words = \App\Support\Storefront::ribbonWords())
@if ($words)
    <section @class(['overflow-hidden py-3', 'section-gap' => ! $first]) aria-label="{{ implode(', ', $words) }}">
        <div class="-mx-4 -rotate-1 overflow-hidden bg-brand-400 text-ink shadow-sm">
        <div class="flex w-max py-3.5 motion-safe:animate-marquee motion-reduce:w-full motion-reduce:flex-wrap motion-reduce:justify-center sm:py-4">
            @foreach ([1, 2] as $copy)
                <ul class="flex shrink-0 items-center" @if ($copy === 2) aria-hidden="true" @endif>
                    @foreach ($words as $word)
                        <li class="flex items-center gap-4 px-4 font-display text-lg font-semibold whitespace-nowrap italic sm:gap-6 sm:px-6 sm:text-2xl">
                            <span class="flower-dot size-4 text-white sm:size-5" aria-hidden="true"></span>{{ $word }}
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
        </div>
    </section>
@endif
