{{-- Kenapa Cheery Chic: pita kakao bermotif bunga emas, poin dalam kartu berbingkai daun (diatur di Admin › Tampilan Beranda) --}}
@php
    use App\Support\Storefront;
    $whyTitle = Storefront::text('why_title') ?? (string) config('shop.motto');
    $points = Storefront::whyPoints();
@endphp
<section class="section-gap relative isolate bg-ink text-white" aria-labelledby="home-why">
    <span class="scallop-up absolute inset-x-0 -top-3.5 text-ink" aria-hidden="true"></span>
    <div class="ornament-bg absolute inset-0 -z-10" aria-hidden="true"></div>
    <div class="shop-container py-16 lg:py-24">
        <div class="section-head">
            <x-logo variant="mark" dark class="h-14" aria-hidden="true" />
            <p class="eyebrow mt-4 text-brand-300">{{ Storefront::text('why_eyebrow', 'Kenapa '.config('shop.name')) }}</p>
            <h2 id="home-why" class="display mt-3 text-[2.25rem] leading-[1.08] sm:text-5xl lg:text-6xl">
                @foreach (array_map('trim', explode('•', $whyTitle)) as $line)
                    <span @class(['block', 'text-brand-300 italic' => $loop->iteration === 2])>{{ $line }}</span>
                @endforeach
            </h2>
            <p class="mt-5 max-w-xl leading-relaxed text-white/65">{{ Storefront::text('why_text', config('shop.tagline').'. Dipilih dan dicek satu per satu supaya kamu tinggal pakai dan merasa nyaman seharian.') }}</p>
        </div>
        @if ($points)
            <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:mt-16 lg:grid-cols-4 lg:gap-6">
                @foreach ($points as ['icon' => $icon, 'title' => $title, 'text' => $desc])
                    <li class="arch-soft flex flex-col items-start border border-white/10 bg-white/[0.05] p-6 sm:p-7">
                        <span class="flex w-full items-center justify-between">
                            <span class="grid size-12 place-items-center rounded-full bg-brand-400 text-ink"><x-icon :name="$icon" class="size-5" /></span>
                            <span class="font-display text-3xl font-semibold text-white/15 italic">0{{ $loop->iteration }}</span>
                        </span>
                        <h3 class="mt-5 text-base font-bold">{{ $title }}</h3>
                        @if ($desc)<p class="mt-1.5 text-sm leading-relaxed text-white/60">{{ $desc }}</p>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <span class="scallop absolute inset-x-0 -bottom-3.5 text-ink" aria-hidden="true"></span>
</section>
