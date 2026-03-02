<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Keripik Singkong Pedas',
                'description' => 'Keripik singkong renyah dengan bumbu balado pedas manis khas buatan rumah. Tanpa pengawet.',
                'price' => 15000,
                'seller_name' => 'Ibu Siti',
                'seller_phone' => '6281234567890',
                'category' => 'Kuliner',
                'rt_id' => 1, // Assuming RT 1 exists
                'is_active' => true,
            ],
            [
                'name' => 'Tas Anyaman Pandan',
                'description' => 'Tas belanja ramah lingkungan dari anyaman pandan laut. Kuat dan modis.',
                'price' => 75000,
                'seller_name' => 'Pak Budi - Kelompok Tani',
                'seller_phone' => '6281987654321',
                'category' => 'Kerajinan',
                'rt_id' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Jasa Jahit & Permak',
                'description' => 'Menerima jahitan baju seragam, gamis, dan permak jeans. Hasil rapi dan cepat.',
                'price' => 25000, // Starting price
                'seller_name' => 'Bu Rahma - UMKM Sejahtera',
                'seller_phone' => '6285678901234',
                'category' => 'Jasa',
                'rt_id' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Kemeja Batik Cap',
                'description' => 'Kemeja batik katun prima dengan motif parang modern. Tersedia ukuran M, L, XL.',
                'price' => 120000,
                'seller_name' => 'Pak Ahmad',
                'seller_phone' => '628111222333',
                'category' => 'Kerajinan',
                'rt_id' => 3,
                'is_active' => true,
            ],
             [
                'name' => 'Sambal Bawang Botol',
                'description' => 'Sambal bawang super pedas, cocok untuk teman makan nasi hangat.',
                'price' => 20000,
                'seller_name' => 'Ibu Wati',
                'seller_phone' => '628555666777',
                'category' => 'Kuliner',
                'rt_id' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
