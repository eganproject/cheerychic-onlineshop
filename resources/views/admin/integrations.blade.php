@php
    // Sumber nilai yang sedang berlaku, ditampilkan di bawah tiap isian.
    $source = function (string $integration, string $name) use ($fields) {
        $field = $fields[$integration][$name];
        if ($field['stored']) {
            return 'Tersimpan di panel admin.'.(filled($field['default']) && ! is_bool($field['default']) ? ' Kosongkan untuk kembali ke .env.' : '');
        }

        return filled($field['value']) ? 'Saat ini dari .env.' : null;
    };
    $value = fn (string $integration, string $name) => $fields[$integration][$name]['value'];
    $cards = [
        'midtrans' => ['Midtrans', 'Payment gateway (Snap): transfer bank, e-wallet, kartu kredit, QRIS.', 'credit-card', 'bg-brand-50 text-brand-600'],
        'rajaongkir' => ['RajaOngkir', 'Cek ongkos kirim & lacak resi JNE, SiCepat, J&T, POS, TIKI, dan lainnya.', 'truck', 'bg-sky-50 text-sky-600'],
        'firebase' => ['Login Google (Firebase)', 'Pelanggan masuk/daftar dengan akun Google. Panduan: docs/FIREBASE.md.', 'user', 'bg-amber-50 text-amber-600'],
    ];
@endphp
<x-layouts.admin title="Integrasi">
    <x-admin.page-header title="Integrasi" description="Driver dan kunci API payment gateway, ongkos kirim, dan login Google. Halaman ini hanya bisa dibuka Superadmin." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            @foreach ($cards as $key => [$name, $description, $icon, $iconClass])
                <section id="{{ $key }}" class="card min-w-0 scroll-mt-24 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $iconClass }}"><x-icon :name="$icon" /></span>
                            <div>
                                <h2 class="font-bold text-stone-900">{{ $name }}</h2>
                                <p class="text-xs text-stone-500">{{ $description }}</p>
                            </div>
                        </div>
                        <span class="badge shrink-0 {{ $status[$key]['class'] }}">{{ $status[$key]['label'] }}</span>
                    </div>

                    @if ($result = $results[$key] ?? null)
                        <div @class([
                            'mt-5 flex gap-3 rounded-xl p-3 text-sm',
                            'bg-emerald-50 text-emerald-800' => $result['ok'],
                            'bg-rose-50 text-rose-800' => ! $result['ok'],
                        ]) role="status">
                            <x-icon :name="$result['ok'] ? 'check-circle' : 'x-circle'" class="size-5 shrink-0" />
                            <div>
                                <p class="font-semibold">{{ $result['ok'] ? 'Koneksi berhasil' : 'Koneksi gagal' }}: {{ $result['message'] }}</p>
                                @foreach ($result['notes'] as $note)
                                    <p class="mt-1 text-xs">• {{ $note }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.integrations.update', $key) }}" class="mt-5 space-y-5" autocomplete="off">
                        @csrf
                        @method('PUT')

                        @if ($key === 'midtrans')
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-form.select name="midtrans[driver]" id="midtrans_driver" label="Driver pembayaran" :selected="$value('midtrans', 'driver')"
                                               :options="['fake' => 'Simulasi (fake)', 'midtrans' => 'Midtrans']" :hint="$source('midtrans', 'driver')"
                                               tip="Simulasi: pembayaran pura-pura untuk mencoba alur checkout tanpa uang sungguhan. Midtrans: pembeli membayar lewat halaman Midtrans (VA bank, e-wallet, QRIS, kartu)."
                                               :tips="['Pakai Simulasi saat toko masih disiapkan; ganti ke Midtrans setelah kunci diisi dan Tes koneksi berhasil.']" />
                                <div>
                                    <div class="label-row">
                                        <p class="label">Mode</p>
                                        <x-tip title="Mode Midtrans" text="Sandbox = lingkungan uji coba Midtrans, tidak ada uang yang berpindah. Production = transaksi sungguhan ke rekening toko."
                                            :tips="['Kunci Sandbox dan Production berbeda; pastikan kunci yang diisi sesuai mode.', 'Uji dulu satu transaksi di Sandbox sebelum pindah ke Production.']" />
                                    </div>
                                    @php($production = (bool) old('midtrans.is_production', $value('midtrans', 'is_production')))
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach (['0' => 'Sandbox', '1' => 'Production'] as $mode => $text)
                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-stone-200 px-3 py-2.5 text-sm font-semibold text-stone-700 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                                <input type="radio" name="midtrans[is_production]" value="{{ $mode }}" class="checkbox rounded-full" @checked($production === (bool) $mode)>
                                                {{ $text }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="hint">Production = uang sungguhan. Kunci Sandbox berawalan "SB-".</p>
                                </div>
                                <x-form.input name="midtrans[merchant_id]" id="midtrans_merchant_id" label="Merchant ID" :value="$value('midtrans', 'merchant_id')" placeholder="G123456789" optional :hint="$source('midtrans', 'merchant_id')"
                                    tip="ID akun merchant Midtrans (diawali G). Ada di Midtrans Dashboard › Settings › Access Keys. Hanya sebagai catatan, tidak wajib untuk pembayaran." />
                                <x-form.input name="midtrans[client_key]" id="midtrans_client_key" label="Client key" :value="$value('midtrans', 'client_key')" placeholder="SB-Mid-client-..." :hint="$source('midtrans', 'client_key')" spellcheck="false"
                                    tip="Kunci publik untuk membuka jendela pembayaran Midtrans (Snap) di browser pembeli. Ada di Midtrans Dashboard › Settings › Access Keys." />
                            </div>
                            @include('admin.partials.secret-input', ['integration' => 'midtrans', 'name' => 'server_key', 'label' => 'Server key', 'placeholder' => 'SB-Mid-server-...',
                                'tip' => 'Kunci rahasia untuk membuat transaksi dan memverifikasi notifikasi pembayaran dari Midtrans. Ada di Midtrans Dashboard › Settings › Access Keys.',
                                'tips' => ['Jangan pernah dibagikan atau ditempel di chat; siapa pun yang memegangnya bisa membuat transaksi atas nama toko.']])

                            <div>
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-stone-500 uppercase">URL notifikasi (webhook) <x-tip title="URL notifikasi" text="Alamat yang dihubungi Midtrans setiap status pembayaran berubah, supaya pesanan otomatis jadi “Dibayar” tanpa dicek manual."
                                    :tips="['Wajib diisi di Midtrans agar status pesanan ter-update.', 'Hanya bisa dihubungi bila situs sudah online (punya domain). Di Laragon/lokal, webhook tidak akan sampai.']" /></p>
                                <div class="mt-2 flex items-center gap-2" x-data="{ copied: false }">
                                    <code class="min-w-0 flex-1 truncate rounded-xl bg-stone-50 px-3 py-2 text-xs text-stone-700 ring-1 ring-stone-200">{{ $webhook }}</code>
                                    <button type="button" class="btn btn-secondary btn-sm" x-on:click="navigator.clipboard.writeText(@js($webhook)); copied = true; setTimeout(() => copied = false, 1500)">
                                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                                    </button>
                                </div>
                                <p class="hint">Tempel di Midtrans Dashboard › Settings › Payment › Notification URL.</p>
                            </div>
                        @elseif ($key === 'rajaongkir')
                            <x-form.select name="rajaongkir[driver]" id="rajaongkir_driver" label="Driver ongkir" :selected="$value('rajaongkir', 'driver')"
                                           :options="['fake' => 'Simulasi (tarif contoh)', 'rajaongkir' => 'RajaOngkir (Komerce)']" :hint="$source('rajaongkir', 'driver')"
                                           tip="Simulasi: ongkir memakai tarif contoh (tidak akurat). RajaOngkir: tarif asli kurir sesuai berat & alamat, plus lacak resi."
                                           :tips="['Pakai RajaOngkir sebelum toko dibuka untuk umum supaya ongkir tidak rugi.']" />
                            @include('admin.partials.secret-input', ['integration' => 'rajaongkir', 'name' => 'api_key', 'label' => 'API key', 'placeholder' => 'API key dari dashboard Komerce',
                                'tip' => 'Kunci akses RajaOngkir untuk cek tarif & lacak resi. Didapat dari dashboard Komerce (layanan RajaOngkir) setelah mendaftar.',
                                'tips' => ['Perhatikan kuota panggilan paket RajaOngkir Anda; tiap checkout memakai beberapa panggilan.']])
                            <div class="rounded-xl bg-stone-50 p-3 text-sm">
                                <p class="text-xs text-stone-500">Kecamatan asal pengiriman</p>
                                <p class="font-semibold text-stone-800">{{ $originLabel ?? 'Belum diatur' }}</p>
                                <a href="{{ route('admin.store-settings.edit') }}" class="mt-1 inline-block text-xs font-semibold text-brand-700 underline">Ubah asal & kurir di Pengaturan Toko</a>
                            </div>
                        @else
                            <label class="flex items-start justify-between gap-4 rounded-xl border border-stone-200 p-4">
                                <span>
                                    <span class="flex items-center gap-1.5 text-sm font-semibold text-stone-800">Tampilkan tombol "Lanjutkan dengan Google" <x-tip title="Login Google" text="Pelanggan bisa daftar/masuk sekali klik dengan akun Google, tanpa membuat password. Akun staf tetap login dengan email & password."
                                        :tips="['Mempercepat pendaftaran dan biasanya menaikkan jumlah pembeli yang menyelesaikan checkout.', 'Tambahkan domain toko di Firebase › Authentication › Authorized domains.']" /></span>
                                    <span class="text-xs text-stone-500">Di halaman masuk & daftar pelanggan. Butuh API key dan Project ID.</span>
                                </span>
                                <input type="hidden" name="firebase[login_enabled]" value="0">
                                <input type="checkbox" name="firebase[login_enabled]" value="1" class="checkbox mt-0.5 size-5 shrink-0" @checked(old('firebase.login_enabled', $value('firebase', 'login_enabled')))>
                            </label>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-form.input name="firebase[api_key]" id="firebase_api_key" label="API key (apiKey)" :value="$value('firebase', 'api_key')" placeholder="AIza..." :hint="$source('firebase', 'api_key')" spellcheck="false"
                                              tip="Nilai apiKey dari konfigurasi Web app Firebase (diawali AIza). Bukan rahasia, tapi tetap batasi domainnya di Google Cloud Console." />
                                <x-form.input name="firebase[project_id]" id="firebase_project_id" label="Project ID (projectId)" :value="$value('firebase', 'project_id')" placeholder="cheerychic-shop" :hint="$source('firebase', 'project_id')" spellcheck="false"
                                              tip="ID proyek Firebase (projectId). Dipakai server untuk memastikan token login Google memang berasal dari proyek Anda." />
                                <x-form.input name="firebase[auth_domain]" id="firebase_auth_domain" label="Auth domain (authDomain)" :value="$value('firebase', 'auth_domain')" placeholder="cheerychic-shop.firebaseapp.com" optional
                                              :hint="$source('firebase', 'auth_domain') ?? 'Kosong = <project-id>.firebaseapp.com.'" spellcheck="false"
                                              tip="Domain halaman login Google milik Firebase. Biasanya cukup dikosongkan." />
                                <x-form.input name="firebase[app_id]" id="firebase_app_id" label="App ID (appId)" :value="$value('firebase', 'app_id')" placeholder="1:1234:web:abcd" optional :hint="$source('firebase', 'app_id')" spellcheck="false"
                                              tip="ID aplikasi web Firebase (appId). Opsional, hanya untuk melengkapi konfigurasi." />
                            </div>
                            <p class="hint">Nilai ini dari Firebase Console › Project settings › Your apps (Web app). Bukan rahasia: ikut terkirim ke browser pelanggan.</p>
                        @endif

                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-stone-100 pt-4">
                            <button type="submit" form="test-{{ $key }}" class="btn btn-secondary">
                                <x-icon name="refresh" class="size-4" /> Tes koneksi
                            </button>
                            <button class="btn btn-primary">Simpan {{ $name }}</button>
                        </div>
                    </form>
                    <form id="test-{{ $key }}" method="POST" action="{{ route('admin.integrations.test', $key) }}" class="hidden">@csrf</form>
                </section>
            @endforeach
        </div>

        <div class="space-y-4">
            <div class="flex gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                <x-icon name="lock" class="size-5 shrink-0" />
                <div class="space-y-2">
                    <p>Server key & API key disimpan terenkripsi dengan <code class="font-mono">APP_KEY</code> dan tidak pernah ditampilkan utuh. Kosongkan isian untuk tetap memakai kunci lama.</p>
                    <p>Isian yang kosong memakai nilai dari file <code class="font-mono">.env</code> di server. Bila <code class="font-mono">APP_KEY</code> diganti, isi ulang kunci di sini.</p>
                </div>
            </div>
            <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <x-icon name="info" class="size-5 shrink-0" />
                <p>"Tes koneksi" memakai kunci yang sudah disimpan. Simpan dulu, lalu tes. Saat driver masih simulasi, checkout tetap bisa dicoba dengan tarif contoh dan pembayaran simulasi.</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
