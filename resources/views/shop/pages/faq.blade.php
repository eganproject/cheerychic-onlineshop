<div class="space-y-3">
    @foreach ($faq as $item)
        <details class="group card p-5" @if ($loop->first) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink">
                <span>{{ $item['q'] }}</span>
                <x-icon name="chevron-down" class="size-4 shrink-0 text-stone-400 transition group-open:rotate-180" />
            </summary>
            <p class="mt-3 text-[15px] leading-relaxed text-stone-600">{{ $item['a'] }}</p>
        </details>
    @endforeach
</div>
<p>Pertanyaanmu belum terjawab? <a href="{{ route('pages.show', 'kontak') }}">Hubungi kami</a>, kami senang membantu.</p>
