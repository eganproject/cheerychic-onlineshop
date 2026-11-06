@php($days = config('shop.policy.return_days'))
<p>Kami ingin kamu puas dengan setiap pesanan. Bila ukuran kurang pas atau barang yang diterima bermasalah, kamu bisa mengajukan tukar dalam <strong>{{ $days }} hari sejak paket diterima</strong>.</p>

<h2>Bisa ditukar bila</h2>
<ul>
    <li>Ukuran kurang pas (tukar ke ukuran atau motif lain pada produk yang sama, selama stok ada).</li>
    <li>Barang cacat produksi, misalnya jahitan lepas atau bahan sobek.</li>
    <li>Barang yang dikirim tidak sesuai pesanan (salah model, motif, atau jumlah).</li>
</ul>

<h2>Syarat</h2>
<ul>
    <li>Pengajuan paling lambat {{ $days }} hari setelah paket diterima.</li>
    <li>Barang belum dipakai, belum dicuci, tidak berbau, dan label masih terpasang.</li>
    <li>Sertakan video unboxing untuk klaim barang cacat atau salah kirim.</li>
    <li>Produk yang dibeli saat promo tetap bisa ditukar, tetapi tidak bisa diuangkan.</li>
</ul>

<h2>Cara mengajukan tukar</h2>
<ol>
    <li>Hubungi kami lewat <a href="{{ route('pages.show', 'kontak') }}">kontak resmi</a> dengan menyebut nomor pesanan, alasan tukar, dan foto/video barang.</li>
    <li>Setelah disetujui, kirim barang ke alamat yang kami berikan.</li>
    <li>Barang pengganti kami kirim setelah barang tukar kami terima dan periksa.</li>
</ol>

<h2>Ongkos kirim</h2>
<p>Untuk barang cacat atau salah kirim, ongkir pengiriman ulang ditanggung {{ config('shop.name') }}. Untuk tukar ukuran atau motif, ongkir dua arah ditanggung pembeli.</p>

<h2>Pengembalian dana</h2>
<p>Pengembalian dana hanya berlaku bila barang pengganti tidak tersedia, atau pesanan dibatalkan sebelum dikirim. Dana dikembalikan ke rekening atau metode pembayaran yang disepakati.</p>
