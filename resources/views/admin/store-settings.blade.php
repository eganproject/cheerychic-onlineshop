@php
    $val = fn (string $field) => old($field, $values[$field] ?? null);
    // Nilai bawaan .env sebagai petunjuk (hanya teks biasa).
    $envHint = fn (string $field) => filled($defaults[$field] ?? null) && ! is_array($defaults[$field]) && ! is_bool($defaults[$field])
        ? 'Kosongkan untuk memakai nilai .env: '.$defaults[$field]
        : null;
    $selectedCouriers = old('couriers', $values['couriers'] ?? []);
@endphp
<x-layouts.admin title="Pengaturan Toko">
    <x-admin.page-header title="Pengaturan Toko" description="Identitas, kontak, pengiriman, dan pembayaran. Perubahan langsung berlaku di seluruh toko." />

    <form method="POST" action="{{ route('admin.store-settings.update') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')

        <div class="space-y-6 lg:col-span-2">
            {{-- Identitas --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="storefront" /></span>
                    <div><h2 class="font-bold text-stone-900">Identitas toko</h2><p class="text-xs text-stone-500">Tampil di judul halaman, email, invoice, dan footer.</p></div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" label="Nama singkat" :value="$values['name']" required maxlength="30"
                        tip="Nama pendek toko di teks sehari-hari, mis. “Kenapa Cheery Chic”, sapaan email, dan pesan WhatsApp."
                        :tips="['Cukup 1–2 kata, sama dengan nama yang dikenal pelanggan.']" />
                    <x-form.input name="full_name" label="Nama lengkap" :value="$values['full_name']" required maxlength="80"
                        tip="Nama resmi toko di judul tab browser, hasil Google, invoice, footer, dan pratinjau link."
                        :tips="['Gabungkan nama + jenis produk, mis. “Cheery Chic Daster”, supaya mudah dikenali di Google.']" />
                </div>
                <x-form.input name="tagline" label="Tagline" :value="$values['tagline']" maxlength="160" optional
                    tip="Slogan singkat di footer dan judul tab beranda (setelah nama toko), juga dipakai di deskripsi bawaan produk tanpa deskripsi."
                    :tips="['Pendek & khas, mis. “Cantik di Rumah, Ceria Setiap Hari”. Di bawah ±60 huruf.']" />
                <x-form.textarea name="address" label="Alamat gudang / pengirim" :value="$values['address']" rows="3" maxlength="500" optional
                                 placeholder="Jl. ..., Kecamatan, Kota, Kode pos"
                                 :hint="trim('Dicetak di invoice dan packing slip. '.$envHint('address'))"
                                 tip="Alamat pengirim yang dicetak di invoice dan label packing. Bukan untuk hitung ongkir (itu memakai Kecamatan asal di bagian Pengiriman)."
                                 :tips="['Tulis lengkap sampai kode pos supaya paket retur bisa kembali dengan benar.']" />
            </section>

            {{-- Kontak & sosial --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="chat" /></span>
                    <div><h2 class="font-bold text-stone-900">Kontak & media sosial</h2><p class="text-xs text-stone-500">Dipakai untuk tombol WhatsApp dan tautan profil toko.</p></div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.phone name="whatsapp" label="No. WhatsApp" :value="$values['whatsapp']" placeholder="0812-3456-7890" hint="Disimpan dalam format 62xxx." optional
                        tip="Nomor tujuan tombol WhatsApp melayang di toko (pesannya otomatis menyebut produk yang sedang dilihat) dan link “Tanya ketersediaan” pada produk yang habis."
                        :tips="['Pakai nomor WhatsApp Business yang aktif dibalas, idealnya dengan pesan sambutan otomatis.']" />
                    <x-form.input type="email" name="email" label="Email kontak" icon="envelope" :value="$values['email']" placeholder="halo@tokoanda.com" optional
                        tip="Email yang ditampilkan di footer toko supaya pelanggan bisa menghubungi Anda. Bukan email login admin." />
                    <x-form.input name="instagram" label="Instagram" prefix="@" :value="$values['instagram']" placeholder="username" optional
                        tip="Cukup username tanpa @ dan tanpa link, mis. cheerychic.id. Tautannya dibuat otomatis di footer." />
                    <x-form.input name="tiktok" label="TikTok" prefix="@" :value="$values['tiktok']" placeholder="username" optional
                        tip="Cukup username tanpa @, tautannya dibuat otomatis di footer." />
                    <x-form.input type="url" name="facebook" label="Halaman Facebook" icon="external" :value="$values['facebook']" placeholder="https://facebook.com/namatoko" optional wrapper-class="sm:col-span-2" />
                </div>
            </section>

            {{-- Pengiriman --}}
            <section class="card space-y-5 p-6"
                     x-data="regionPicker({
                         urls: {
                             provinces: @js(route('admin.regions.provinces')),
                             cities: @js(route('admin.regions.cities', '__ID__')),
                             districts: @js(route('admin.regions.districts', '__ID__')),
                         },
                         provinceId: @js($val('province_id')),
                         cityId: @js($val('city_id')),
                         districtId: @js($val('district_id')),
                     })">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-sky-50 text-sky-600"><x-icon name="truck" /></span>
                    <div><h2 class="font-bold text-stone-900">Pengiriman</h2><p class="text-xs text-stone-500">Asal pengiriman dipakai untuk menghitung ongkir di checkout.</p></div>
                </div>

                <div class="rounded-xl bg-stone-50 p-3 text-sm">
                    <p class="text-xs text-stone-500">Kecamatan asal saat ini</p>
                    <p class="font-semibold text-stone-800">{{ $values['origin_label'] ?: ($values['district_id'] ? 'ID '.$values['district_id'] : 'Belum diatur') }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    @foreach ([
                        ['province', 'province_id', 'Provinsi'],
                        ['city', 'city_id', 'Kota / Kabupaten'],
                        ['district', 'district_id', 'Kecamatan'],
                    ] as [$ref, $field, $text])
                        <x-form.field :label="$text" :for="$field" :name="$field"
                            :tip="$field === 'district_id' ? 'Kecamatan gudang tempat paket dikirim. Ongkir di checkout dihitung dari kecamatan ini ke alamat pembeli.' : null"
                            :tips="$field === 'district_id' ? ['Pastikan sama dengan lokasi drop-off/pickup kurir, supaya ongkir yang dibayar pembeli sesuai tagihan kurir.'] : []">
                            <select id="{{ $field }}" name="{{ $field }}" x-ref="{{ $ref }}" @class(['input', 'input-error' => $errors->has($field)])></select>
                        </x-form.field>
                    @endforeach
                </div>
                <p x-show="error" x-cloak x-text="error" class="flex gap-2 rounded-xl bg-rose-50 p-3 text-xs text-rose-700"></p>
                <p class="hint">Biarkan seperti semula bila kecamatan asal tidak berubah.</p>

                <div>
                    <div class="label-row">
                        <p class="label">Kurir yang ditawarkan di checkout</p>
                        <x-tip title="Kurir" text="Hanya kurir yang dicentang yang tarifnya dicek dan ditawarkan ke pembeli saat checkout."
                            :tips="['Pilih 3–5 kurir yang tersedia di kota Anda. Terlalu banyak membuat checkout lebih lambat.', 'Sertakan satu opsi murah (mis. J&T/SiCepat) dan satu yang luas jangkauannya (JNE/POS).']" />
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($couriers as $code => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-stone-200 px-3 py-2.5 text-sm font-semibold text-stone-700 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="checkbox" name="couriers[]" value="{{ $code }}" class="checkbox" @checked(in_array($code, $selectedCouriers, true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @if ($shippingDriver === 'fake')
                        <p class="mt-1.5 text-xs text-stone-400">Mode simulasi hanya punya tarif contoh JNE, SiCepat, J&T, POS, dan TIKI.</p>
                    @endif
                    <x-input-error for="couriers" />
                </div>

                <label class="flex items-start justify-between gap-4 rounded-xl border border-stone-200 p-4">
                    <span>
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-stone-800">Cek resi otomatis <x-tip title="Cek resi otomatis" text="Pesanan berstatus Dikirim dicek ke kurir tiap 3 jam lewat RajaOngkir. Yang sudah diterima ditandai Sampai otomatis, lalu selesai sendiri setelah beberapa hari."
                            :tips="['Butuh RajaOngkir aktif dan cron scheduler di server (php artisan schedule:run tiap menit).', 'Di lokal/Laragon biarkan mati bila scheduler tidak berjalan.']" /></span>
                        <span class="text-xs text-stone-500">Setiap 3 jam status resi pesanan "Dikirim" dicek ke kurir; yang sudah sampai ditandai otomatis. Butuh cron scheduler aktif.</span>
                    </span>
                    <input type="hidden" name="tracking_sync" value="0">
                    <input type="checkbox" name="tracking_sync" value="1" class="checkbox mt-0.5 size-5 shrink-0" @checked(old('tracking_sync', $values['tracking_sync']))>
                </label>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Pesanan & pembayaran --}}
            <section class="card space-y-5 p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="clock" /></span>
                    <div><h2 class="font-bold text-stone-900">Pesanan & pembayaran</h2></div>
                </div>
                <x-form.number name="payment_expiry_hours" label="Batas waktu bayar" :value="$values['payment_expiry_hours']" min="1" max="168" required suffix="jam"
                               hint="Lewat dari ini pesanan dibatalkan otomatis dan stok kembali. Berlaku untuk pesanan baru."
                               tip="Lama waktu pembeli boleh menunda pembayaran setelah checkout. Selama itu stok barangnya “dipesan” dan tidak bisa dibeli orang lain."
                               :tips="['24 jam umumnya pas. Terlalu lama membuat stok tertahan oleh pesanan yang tidak jadi dibayar.']" />
                <x-form.number name="auto_complete_days" label="Selesai otomatis setelah sampai" :value="$values['auto_complete_days']" min="1" max="30" required suffix="hari"
                               hint='Bila pelanggan tidak menekan "Pesanan diterima".'
                               tip="Pesanan berstatus Sampai otomatis jadi Selesai setelah sekian hari bila pelanggan lupa konfirmasi. Pesanan Selesai dihitung di laporan & bisa diulas."
                               :tips="['3–7 hari memberi waktu pelanggan untuk komplain sebelum pesanan ditutup.']" />
            </section>

            <div class="flex gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                <x-icon name="info" class="size-5 shrink-0" />
                @can('integrations.manage')
                    <p>Driver & kunci API Midtrans, RajaOngkir, dan Firebase diatur di <a href="{{ route('admin.integrations.index') }}" class="font-semibold underline">Integrasi</a>.</p>
                @else
                    <p>Driver & kunci API Midtrans, RajaOngkir, dan Firebase hanya bisa diatur oleh Superadmin.</p>
                @endcan
            </div>

            <div class="card sticky top-20 p-4">
                <button class="btn btn-primary w-full">Simpan pengaturan</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
