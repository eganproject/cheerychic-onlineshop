{{--
    Pemberitahuan bahwa pesanan/keranjang berisi produk Pre Order.
    days: masa proses PO terlama. readyAt: perkiraan siap kirim (opsional, untuk pesanan yang sudah ada).
--}}
@props(['days', 'readyAt' => null, 'mixed' => false])
<div {{ $attributes->class('flex gap-3 rounded-2xl border border-sage-200 bg-sage-50 p-4 text-sm text-stone-600') }} role="note">
    <x-icon name="clock" class="mt-0.5 size-5 shrink-0 text-sage-600" />
    <div class="min-w-0">
        <p class="font-semibold text-ink">Ada produk Pre Order</p>
        <p class="mt-0.5">
            @if ($readyAt)
                Perkiraan siap dikirim sekitar <strong class="font-semibold text-ink">{{ $readyAt->translatedFormat('j F Y') }}</strong> (±{{ $days }} hari setelah pembayaran).
            @else
                Pesanan dikirim ±{{ $days }} hari setelah pembayaran, menunggu masa produksi selesai.
            @endif
            @if ($mixed)
                Produk ready stock dikirim bersamaan dengan produk PO.
            @endif
        </p>
    </div>
</div>
