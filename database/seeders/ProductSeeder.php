<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RT;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get RT IDs with fallbacks
        $rt1 = RT::where('kode', '01')->first()?->id ?? 1;
        $rt2 = RT::where('kode', '02')->first()?->id ?? 2;
        $rt3 = RT::where('kode', '03')->first()?->id ?? 3;
        $rt4 = RT::where('kode', '04')->first()?->id ?? 4;
        $rt5 = RT::where('kode', '05')->first()?->id ?? 5;
        $rt6 = RT::where('kode', '06')->first()?->id ?? 6;
        $rt7 = RT::where('kode', '07')->first()?->id ?? 7;

        $products = [
            [
                'name' => 'Keripik Singkong Pedas',
                'description' => 'Keripik singkong renyah dengan bumbu balado pedas manis khas buatan rumah. Tanpa pengawet.',
                'price' => 15000,
                'seller_name' => 'Ibu Siti',
                'seller_phone' => '6281234567890',
                'category' => 'Kuliner',
                'rt_id' => $rt1,
                'is_active' => true,
            ],
            [
                'name' => 'Tas Anyaman Pandan',
                'description' => 'Tas belanja ramah lingkungan dari anyaman pandan laut. Kuat dan modis.',
                'price' => 75000,
                'seller_name' => 'Pak Budi - Kelompok Tani',
                'seller_phone' => '6281987654321',
                'category' => 'Kerajinan',
                'rt_id' => $rt2,
                'is_active' => true,
            ],
            [
                'name' => 'Jasa Jahit & Permak',
                'description' => 'Menerima jahitan baju seragam, gamis, dan permak jeans. Hasil rapi dan cepat.',
                'price' => 25000,
                'seller_name' => 'Bu Rahma - UMKM Sejahtera',
                'seller_phone' => '6285678901234',
                'category' => 'Jasa',
                'rt_id' => $rt1,
                'is_active' => true,
            ],
            [
                'name' => 'Kemeja Batik Cap',
                'description' => 'Kemeja batik katun prima dengan motif parang modern. Tersedia ukuran M, L, XL.',
                'price' => 120000,
                'seller_name' => 'Pak Ahmad',
                'seller_phone' => '628111222333',
                'category' => 'Kerajinan',
                'rt_id' => $rt3,
                'is_active' => true,
            ],
            [
                'name' => 'Sambal Bawang Botol',
                'description' => 'Sambal bawang super pedas, cocok untuk teman makan nasi hangat.',
                'price' => 20000,
                'seller_name' => 'Ibu Wati',
                'seller_phone' => '628555666777',
                'category' => 'Kuliner',
                'rt_id' => $rt2,
                'is_active' => true,
            ],
            [
                'name' => 'Keripik Usus Renyah',
                'description' => 'Keripik usus ayam pilihan dengan bumbu rempah tradisional.',
                'price' => 18000,
                'seller_name' => 'Ibu Maria',
                'seller_phone' => '628123445566',
                'category' => 'Kuliner',
                'rt_id' => $rt4,
                'is_active' => true,
            ],
            [
                'name' => 'Gantungan Kunci Kayu Ukir',
                'description' => 'Souvenir khas Desa Kandri dari kayu jati sisa produksi mebel.',
                'price' => 10000,
                'seller_name' => 'Mas Eko',
                'seller_phone' => '628560099887',
                'category' => 'Kerajinan',
                'rt_id' => $rt4,
                'is_active' => true,
            ],
            [
                'name' => 'Madu Klanceng Asli',
                'description' => 'Madu murni dari lebah klanceng (Trigona) asli budidaya warga RT 05.',
                'price' => 85000,
                'seller_name' => 'Pak Haji Mansur',
                'seller_phone' => '6281333444555',
                'category' => 'Kuliner',
                'rt_id' => $rt5,
                'is_active' => true,
            ],
            [
                'name' => 'Jasa Cuci Motor Kinclong',
                'description' => 'Cuci motor dengan sabun khusus dan semir ban. Lokasi strategis.',
                'price' => 15000,
                'seller_name' => 'Rian - Pemuda Kreatif',
                'seller_phone' => '628998877665',
                'category' => 'Jasa',
                'rt_id' => $rt5,
                'is_active' => true,
            ],
            [
                'name' => 'Bandeng Presto Vacuum',
                'description' => 'Bandeng duri lunak dengan kemasan vacuum, tahan lama dan gurih.',
                'price' => 35000,
                'seller_name' => 'Bu Endang',
                'seller_phone' => '6282211223344',
                'category' => 'Kuliner',
                'rt_id' => $rt6,
                'is_active' => true,
            ],
            [
                'name' => 'Keset Perca Karakter',
                'description' => 'Keset dari perca kain katun dengan motif karakter lucu untuk kamar anak.',
                'price' => 20000,
                'seller_name' => 'Kelompok PKK RT 06',
                'seller_phone' => '6287766554433',
                'category' => 'Kerajinan',
                'rt_id' => $rt6,
                'is_active' => true,
            ],
            [
                'name' => 'Aneka Kue Basah (Snack Box)',
                'description' => 'Menerima pesanan lemper, risoles, dan pastel untuk acara pengajian/rapat.',
                'price' => 2500,
                'seller_name' => 'Mbak Sari',
                'seller_phone' => '6285211223344',
                'category' => 'Kuliner',
                'rt_id' => $rt7,
                'is_active' => true,
            ],
            [
                'name' => 'Jasa Service Elektronik',
                'description' => 'Melayani service TV, Magic Com, dan Kipas Angin. Bisa dipanggil ke rumah.',
                'price' => 50000,
                'seller_name' => 'Pak Dedi',
                'seller_phone' => '6281122334455',
                'category' => 'Jasa',
                'rt_id' => $rt7,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product);
        }
    }
}
