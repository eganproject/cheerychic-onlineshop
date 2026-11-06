<x-layouts.shop :title="$title" :description="$description">
    <x-slot:head>
        {!! \App\Support\StructuredData::script(...array_values(array_filter([
            \App\Support\StructuredData::breadcrumbs([
                ['Beranda', route('home')],
                [$title, route('pages.show', $page)],
            ]),
            $faq ? \App\Support\StructuredData::faq($faq) : null,
        ]))) !!}
    </x-slot:head>

    <div class="shop-container pt-8 lg:pt-12">
        <nav class="flex items-center gap-1.5 text-xs text-stone-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-ink">Beranda</a>
            <x-icon name="chevron-right" class="size-3 text-stone-300" />
            <span class="text-stone-700">{{ $title }}</span>
        </nav>

        <div class="mx-auto mt-6 max-w-3xl">
            <p class="kicker text-brand-600">{{ config('shop.name') }}</p>
            <h1 class="display mt-3 text-4xl leading-[1.05] text-ink sm:text-5xl">{{ $title }}</h1>
            <p class="mt-4 text-stone-600">{{ $description }}</p>

            <div class="article-prose mt-10">
                @include('shop.pages.'.$page)
            </div>

            <div class="mt-12 flex flex-wrap gap-3 border-t border-stone-200 pt-8">
                <a href="{{ route('products.index') }}" class="btn btn-brand btn-pill">Lihat koleksi <x-icon name="arrow-right" class="size-4" /></a>
                @foreach (\App\Http\Controllers\Shop\PageController::PAGES as $slug => [$label])
                    @continue($slug === $page)
                    <a href="{{ route('pages.show', $slug) }}" class="chip">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.shop>
