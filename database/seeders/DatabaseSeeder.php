<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data contoh (akun berpassword "password", pesanan palsu) tidak boleh masuk server produksi.
        if (app()->isProduction()) {
            $this->command?->error('Seeder contoh tidak dijalankan di produksi. Buat akun Superadmin dengan: php artisan shop:create-admin');

            return;
        }

        User::updateOrCreate(['email' => 'admin@cheerychic.test'], [
            'name' => 'Admin Cheery Chic',
            'password' => 'password',
            'phone' => '081200000000',
            'role' => UserRole::Admin,
            'staff_role' => StaffRole::Owner,
            'email_verified_at' => now(),
        ]);

        // Contoh staf dengan akses terbatas.
        foreach ([
            ['gudang@cheerychic.test', 'Rudi Hartono', StaffRole::Warehouse],
            ['cs@cheerychic.test', 'Sari Wulandari', StaffRole::CustomerService],
        ] as [$email, $name, $staffRole]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => UserRole::Admin,
                'staff_role' => $staffRole,
                'email_verified_at' => now(),
            ]);
        }

        // Katalog (kategori, produk, foto, varian, stok awal, koleksi) ada di seeder sendiri
        // supaya server baru bisa diisi produk tanpa akun contoh.
        $this->call(CatalogSeeder::class);

        // Data contoh lain (pelanggan, pesanan, ulasan, voucher, artikel) sengaja tidak diisi
        // supaya toko mulai kosong. Untuk demo dashboard/laporan jalankan manual:
        //   php artisan db:seed --class=DemoOrderSeeder
        //   php artisan db:seed --class=ArticleSeeder
    }
}
