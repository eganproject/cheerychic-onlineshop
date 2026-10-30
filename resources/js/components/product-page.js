/**
 * Halaman detail produk: galeri foto + pemilih varian. Memilih varian
 * mengganti harga, stok, dan foto (bila varian punya foto sendiri).
 * Harga grosir (tiers) ikut berlaku begitu jumlah mencapai tingkatnya.
 */
export default (config) => ({
    images: config.images, // [{ id, url }]
    active: 0,
    variants: config.variants, // [{ id, name, price, compare_at_price, stock, image_id }]
    selectedId: config.selectedId ?? null,
    qty: 1,
    tiers: config.tiers ?? [], // [{ min_quantity, price }] urut naik

    get selected() {
        return this.variants.find((variant) => variant.id === this.selectedId) ?? null;
    },

    get stock() {
        return this.selected ? this.selected.stock : config.stock;
    },

    get max() {
        return Math.max(1, this.stock);
    },

    get regularPrice() {
        return this.selected ? this.selected.price : config.price;
    },

    /** Tingkat grosir yang berlaku untuk jumlah saat ini (null bila belum mencapai minimal). */
    get tier() {
        const qty = parseInt(this.qty, 10) || 1;
        return [...this.tiers].reverse().find((tier) => qty >= tier.min_quantity) ?? null;
    },

    get price() {
        return this.tier ? Math.min(this.regularPrice, this.tier.price) : this.regularPrice;
    },

    get wholesale() {
        return this.price < this.regularPrice;
    },

    get compareAt() {
        if (this.wholesale) return this.regularPrice;
        const compare = this.selected ? this.selected.compare_at_price : config.compareAt;
        return compare && compare > this.price ? compare : null;
    },

    /** Pilih tingkat grosir dari tabel: isi jumlah dengan minimalnya. */
    useTier(tier) {
        this.qty = Math.min(this.max, tier.min_quantity);
    },

    get discount() {
        return this.compareAt ? Math.round(100 - (this.price / this.compareAt) * 100) : 0;
    },

    get needsVariant() {
        return this.variants.length > 0 && !this.selected;
    },

    select(variant) {
        if (variant.stock < 1) return;
        this.selectedId = variant.id;
        this.qty = Math.min(this.qty, Math.max(1, variant.stock));
        const imageIndex = this.images.findIndex((image) => image.id === variant.image_id);
        if (imageIndex >= 0) this.active = imageIndex;
    },

    next(step) {
        if (this.images.length < 2) return;
        this.active = (this.active + step + this.images.length) % this.images.length;
    },
});
