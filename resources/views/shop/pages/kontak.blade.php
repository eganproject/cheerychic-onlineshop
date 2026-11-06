@php
    $whatsapp = config('shop.whatsapp');
    $instagram = ltrim((string) config('shop.instagram'), '@');
    $tiktok = ltrim((string) config('shop.tiktok'), '@');
@endphp
<p>Tim {{ config('shop.name') }} siap bantu pilih motif dan ukuran, cek status pesanan, atau menjawab pertanyaan lain.</p>
<ul>
    @if ($whatsapp)
        <li><strong>WhatsApp:</strong> <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">+{{ $whatsapp }}</a> (cara tercepat)</li>
    @endif
    @if (config('shop.email'))
        <li><strong>Email:</strong> <a href="mailto:{{ config('shop.email') }}">{{ config('shop.email') }}</a></li>
    @endif
    @if ($instagram)
        <li><strong>Instagram:</strong> <a href="https://instagram.com/{{ $instagram }}" target="_blank" rel="noopener">&#64;{{ $instagram }}</a></li>
    @endif
    @if ($tiktok)
        <li><strong>TikTok:</strong> <a href="https://www.tiktok.com/@{{ $tiktok }}" target="_blank" rel="noopener">&#64;{{ $tiktok }}</a></li>
    @endif
    @if (config('shop.address'))
        <li><strong>Alamat:</strong> {{ config('shop.address') }}</li>
    @endif
</ul>
@if (! $whatsapp && ! config('shop.email') && ! $instagram && ! $tiktok)
    <p>Kontak toko sedang kami perbarui. Sementara itu, lihat <a href="{{ route('pages.show', 'faq') }}">FAQ</a> kami.</p>
@endif
<p>Untuk pertanyaan tentang pesanan, sertakan nomor pesanan supaya kami bisa membantu lebih cepat.</p>
