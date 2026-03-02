<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Parameter Ekonomi & Zakat
            [
                'key_name' => 'lock_distribusi',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Kunci tabel perhitungan distribusi jika sudah disimpan'
            ],
            [
                'key_name' => 'current_ramadhan_year',
                'value' => '1447H / 2026',
                'type' => 'string',
                'description' => 'Tahun ramadhan aktif'
            ],
            [
                'key_name' => 'minimal_sedekah_amil',
                'value' => '10',
                'type' => 'number',
                'description' => 'Persentase minimal hak amil dari total sedekah'
            ],
            [
                'key_name' => 'nisab_zakat_mall',
                'value' => '85000000',
                'type' => 'number',
                'description' => 'Nilai ambang batas minimal zakat mal tahun ini'
            ],

            // Identitas & Laporan
            [
                'key_name' => 'masjid_name',
                'value' => 'Masjid Baitulmall Kandri',
                'type' => 'string',
                'description' => 'Nama masjid atau lembaga'
            ],
            [
                'key_name' => 'masjid_full_address',
                'value' => 'Jl. Masjid No. 1, Kandri, Semarang',
                'type' => 'string',
                'description' => 'Alamat lengkap untuk footer surat-surat resmi'
            ],

            [
                'key_name' => 'app_logo_url',
                'value' => 'https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png',
                'type' => 'string',
                'description' => 'URL logo organisasi'
            ],

            // Manajemen Event & Panitia
            [
                'key_name' => 'active_event_code',
                'value' => 'RAMADHAN_2026_XYZ',
                'type' => 'string',
                'description' => 'Kode event yang sedang berjalan saat ini'
            ],
            [
                'key_name' => 'max_assignment_period',
                'value' => '2',
                'type' => 'number',
                'description' => 'Default masa jabatan pengurus (tahun)'
            ],

            // Konfigurasi Fitur
            [
                'key_name' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Matikan fitur input data sementara saat Audit'
            ],
            [
                'key_name' => 'enable_online_muzaki',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Aktifkan pendaftaran muzakki melalui link publik'
            ],
            [
                'key_name' => 'show_asnaf_map_public',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Peta asnaf dapat diakses publik'
            ],

            // Data JSON
            [
                'key_name' => 'distribution_priorities',
                'value' => json_encode(['Fakir', 'Miskin', 'Gharim']),
                'type' => 'json',
                'description' => 'Urutan prioritas penerimaan asnaf'
            ],
            [
                'key_name' => 'bank_account_list',
                'value' => json_encode([
                    [
                        'bank' => 'BSI',
                        'account' => '1234567890',
                        'owner' => 'Baitulmall Kandri'
                    ]
                ]),
                'type' => 'json',
                'description' => 'Daftar rekening masjid untuk transfer'
            ]
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key_name' => $s['key_name']], $s);
        }
    }
}
