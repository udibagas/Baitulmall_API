<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImpactStorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ImpactStory::create([
            'title' => 'Beasiswa Mentari: Harapan Baru untuk Rangga',
            'slug' => 'beasiswa-mentari-rangga',
            'content' => 'Rangga, seorang anak yatim di RT 03, kini bisa melanjutkan sekolahnya berkat bantuan beasiswa dari dana Sedekah Baitulmal. Senyumnya kembali merekah saat menerima seragam dan buku baru.',
            'beneficiary_name_masked' => 'Rangga (Yatim, RT 03)',
            'category' => 'Pendidikan',
            'image_path' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&q=80&w=800',
            'is_published' => true,
        ]);

        \App\Models\ImpactStory::create([
            'title' => 'Layanan Kesehatan Gratis: Kesembuhan untuk Ibu Aminah',
            'slug' => 'layanan-kesehatan-ibu-aminah',
            'content' => 'Ibu Aminah sempat kesulitan biaya berobat, namun respon cepat Baitulmal melalui bantuan kesehatan Zakat Mal memastikan beliau mendapatkan perawatan terbaik di RS terdekat.',
            'beneficiary_name_masked' => 'Ibu A*** (Lansia, RT 05)',
            'category' => 'Kesehatan',
            'image_path' => 'https://images.unsplash.com/photo-1576091160550-217359f42f8c?auto=format&fit=crop&q=80&w=800',
            'is_published' => true,
        ]);

        \App\Models\ImpactStory::create([
            'title' => 'Pangan Berkah: Distribusi Fitrah untuk Para Lansia',
            'slug' => 'pangan-berkah-lansia',
            'content' => 'Penyaluran Zakat Fitrah berupa beras kualitas terbaik telah menjangkau seluruh lansia dhuafa di RW 01, memastikan tidak ada lagi warga yang kekurangan pangan di hari raya.',
            'beneficiary_name_masked' => 'Keluarga Bpk. S*** (Lansia, RT 01)',
            'category' => 'Pangan',
            'image_path' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&q=80&w=800',
            'is_published' => true,
        ]);
    }
}
