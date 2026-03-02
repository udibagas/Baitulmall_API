<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Muzaki;
use App\Models\ZakatFitrah;
use App\Models\RT;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmartAssistantController extends Controller
{
    /**
     * Handle chat queries with Rule-based NLP
     */
    /**
     * Handle chat queries with Rule-based NLP
     */
    public function chat(Request $request)
    {
        $query = strtolower($request->input('query'));
        $response = [
            'type' => 'text',
            'message' => 'Maaf, saya belum mengerti pertanyaan tersebut. Coba "Hitung zakat 10 juta", "Cek asnaf di RT 01", atau "Total infak".',
            'data' => null
        ];

        // 1. Intent: Zakat Calculator (Hitung)
        if (str_contains($query, 'hitung') || str_contains($query, 'kalkulasi') || str_contains($query, 'zakatnya berapa')) {
             $response = $this->handleZakatCalculator($query);
        }
        // 2. Intent: Unpaid Muzaki (Belum Bayar)
        else if ((str_contains($query, 'belum') || str_contains($query, 'siapa')) && (str_contains($query, 'bayar') || str_contains($query, 'zakat') || str_contains($query, 'muzaki'))) {
            $response = $this->handleUnpaidMuzaki($query);
        }
        // 3. Intent: Data & Stats (Total, Statistik, Cek Data, List)
        else if (str_contains($query, 'total') || str_contains($query, 'statistik') || str_contains($query, 'jumlah') || str_contains($query, 'data') || str_contains($query, 'cek') || str_contains($query, 'lihat') || str_contains($query, 'daftar')) {
            
            // Sub-intent: Sedekah / Infak
            if (str_contains($query, 'sedekah') || str_contains($query, 'infak') || str_contains($query, 'infaq')) {
                $response = $this->handleSedekahStats($query);
            }
            // Sub-intent: Asnaf (Fakir/Miskin)
            else if (str_contains($query, 'fakir') || str_contains($query, 'miskin') || str_contains($query, 'asnaf')) {
                 $response = $this->handleAsnafStats($query);
            }
            // Sub-intent: Santunan / Yatim / Dhuafa
            else if (str_contains($query, 'yatim') || str_contains($query, 'piatu') || str_contains($query, 'dhuafa')) {
                 $response = $this->handleSantunanStats($query);
            }
            // Sub-intent: Muzaki (General)
            else if (str_contains($query, 'muzaki')) {
                if (str_contains($query, 'belum') || str_contains($query, 'bayar')) {
                    $response = $this->handleUnpaidMuzaki($query);
                } else {
                    $response = $this->handleSummaryStats($query); // Fallback to general stats
                }
            }
            // Default: Zakat Fitrah Stats
            else if (str_contains($query, 'zakat')) {
                $response = $this->handleSummaryStats($query);
            }
        }
        // 4. Intent: Greeting
        else if (str_contains($query, 'halo') || str_contains($query, 'hai') || str_contains($query, 'assalamualaikum')) {
            $response = [
                'type' => 'text',
                'message' => "Assalamu'alaikum Warahmatullahi Wabarakatuh! 🙏\nSaya **Asisten Cerdas Baitulmal**.\n\nSaya siap membantu Anda dengan informasi:\n\n🧮 **Layanan Zakat**\nHitung zakat maal, emas, atau penghasilan.\n\n📊 **Data Kepedulian**\nCek data Fakir Miskin, Yatim Piatu, dan Dhuafa di setiap RT.\n\n🕌 **Laporan Keuangan**\nInfo terkini perolehan Infak dan Zakat Fitrah.\n\nSilakan ketik pertanyaan Anda.",
                'data' => null
            ];
        }
        // 5. Intent Redirect for Documents
        else if (str_contains($query, 'buat') || str_contains($query, 'surat') || str_contains($query, 'undangan')) {
             $response = [
                'type' => 'text',
                'message' => "Mohon maaf, fitur pembuatan surat telah dipindah. Silakan akses menu **Kesekretariatan** di sebelah kiri untuk membuat surat undangan atau surat tugas.",
                'data' => null
            ];
        }

        return response()->json($response);
    } 

    // --- Logic Handlers ---

    private function handleZakatCalculator($query)
    {
        // Extract numbers (nominal)
        preg_match_all('/[\d,.]+/', $query, $matches);
        
        $numbers = [];
        foreach ($matches[0] as $match) {
             // Remove dots/commas
             $clean = str_replace(['.', ','], '', $match);
             if (is_numeric($clean)) {
                 $numbers[] = (float)$clean;
             }
        }

        // Logic: Emas
        if (str_contains($query, 'emas')) {
             $weight = $numbers[0] ?? 0;
             $pricePerGram = 1350000; // Hardcoded fallback or fetch from DB
             
             // Try fetching real gold price
             $goldPriceDB = \App\Models\GoldPrice::latest()->first();
             if ($goldPriceDB) {
                 $pricePerGram = $goldPriceDB->price;
             }

             if ($weight < 85) {
                return [
                    'type' => 'text',
                    'message' => "Nishab zakat emas adalah **85 gram**. Emas Anda ($weight gram) **belum wajib zakat**."
                ];
             }

             $zakat = $weight * $pricePerGram * 0.025;
             return [
                 'type' => 'text',
                 'message' => "📝 **Perhitungan Zakat Emas**\n\n- Berat: $weight gram\n- Harga Emas: " . $this->formatCurrency($pricePerGram) . "/gr\n- Nishab: 85 gram (Wajib)\n\n**Total Zakat yang harus dikeluarkan:**\n💎 **" . $this->formatCurrency($zakat) . "**"
             ];
        }

        // Logic: Zakat Penghasilan (Income)
        if (str_contains($query, 'penghasilan') || str_contains($query, 'gaji') || str_contains($query, 'profesi')) {
             $income = $numbers[0] ?? 0;
             $goldPrice = 1350000;
             $goldPriceDB = \App\Models\GoldPrice::latest()->first();
             if ($goldPriceDB) $goldPrice = $goldPriceDB->price;

             // Nishab Zakat Penghasilan (analogous to 85gr gold / year or 653kg rice / month approx)
             // Let's use 85gr gold / 12 months rule or simpler 524kg rice logic often used in ID
             // For simplicity, let's use a standard monthly nishab estimate ~6-7 mio
             $nishabBulanan = 6800000; 

             if ($income < $nishabBulanan) {
                 return [
                    'type' => 'text',
                    'message' => "Penghasilan Anda " . $this->formatCurrency($income) . " belum mencapai nishab zakat penghasilan bulanan (Est. " . $this->formatCurrency($nishabBulanan) . ")."
                ];
             }

             $zakat = $income * 0.025;
             return [
                'type' => 'text',
                'message' => "💼 **Perhitungan Zakat Penghasilan**\n\n- Penghasilan: " . $this->formatCurrency($income) . "\n- Nishab Bulanan: " . $this->formatCurrency($nishabBulanan) . "\n\n**Zakat wajib (2.5%):**\n💰 **" . $this->formatCurrency($zakat) . "**"
            ];
        }

        // Logic: Zakat Maal (Assets/Money) - Default
        $amount = $numbers[0] ?? 0;
        
        // Sanity check for small numbers parsing (e.g. "rt 05")
        if ($amount < 1000) { 
             return [
                'type' => 'text',
                'message' => "Mohon sebutkan nominal harta/penghasilan yang ingin dihitung zakatnya.\n\nContoh:\n- 'Hitung zakat 100 juta'\n- 'Zakat emas 90 gram'\n- 'Zakat gaji 10 juta'"
            ];
        }

        $nishab = 85 * 1350000; // Est 85gr gold
        // Try fetching real gold price for nishab
        $goldPriceDB = \App\Models\GoldPrice::latest()->first();
        if ($goldPriceDB) {
             $nishab = 85 * $goldPriceDB->price;
        }

        if ($amount < $nishab) {
             return [
                'type' => 'text',
                'message' => "Harta simpanan Anda " . $this->formatCurrency($amount) . " **belum mencapai nishab** zakat maal (" . $this->formatCurrency($nishab) . ")."
            ];
        }

        $zakat = $amount * 0.025;
        return [
            'type' => 'text',
            'message' => "💰 **Perhitungan Zakat Maal**\n\n- Total Harta: " . $this->formatCurrency($amount) . "\n- Nishab: " . $this->formatCurrency($nishab) . "\n\n**Zakat wajib (2.5%):**\n✅ **" . $this->formatCurrency($zakat) . "**"
        ];
    }

    private function handleUnpaidMuzaki($query)
    {
        $year = Date('Y');
        $rtQuery = null;
        $rtNumber = '';

        // Extract RT from query (e.g. "RT 05")
        if (preg_match('/rt\s*(\d+)/', $query, $matches)) {
            $rtNumber = $matches[1]; // "05" or "5"
            // Find RT ID by number (assuming code matches)
            $rt = RT::where('kode', 'like', "%$rtNumber%")->first();
            if ($rt) {
                $rtQuery = $rt->id;
            }
        }

        $queryBuilder = Muzaki::where('tahun', $year)
            ->where(function($q) {
                $q->whereNull('status_bayar')
                  ->orWhere('status_bayar', '!=', 'lunas');
            });

        if ($rtQuery) {
            $queryBuilder->where('rt_id', $rtQuery);
        }

        $unpaid = $queryBuilder->with('rt')->orderBy('nama')->get();
        $count = $unpaid->count();

        $message = "Saat ini terdapat **$count Muzaki** yang belum menunaikan zakat tahun $year";
        if ($rtQuery) {
            $message .= " di Wilayah RT $rtNumber";
        }
        $message .= ".";

        // Format data for simpler display
        $formattedData = $unpaid->map(function($m) {
            return [
                'nama' => $m->nama,
                'rt' => $m->rt ? 'RT '.$m->rt->kode : '-',
                'status' => 'Belum Lunas'
            ];
        })->take(50); // Limit

        if ($count > 0) {
            $message .= " Berikut datanya:";
        } else {
            $message = "Alhamdulillah, seluruh data Muzaki tercatat **LUNAS**" . ($rtQuery ? " di RT $rtNumber" : "") . ". 🎉";
            $formattedData = [];
        }

        return [
            'type' => 'list_muzaki',
            'message' => $message,
            'data' => $formattedData
        ];
    }

    private function handleSummaryStats($query)
    {
        $year = Date('Y');
        
        $totalMuzaki = Muzaki::where('tahun', $year)->count();
        $totalPaid = Muzaki::where('tahun', $year)->where('status_bayar', 'lunas')->count();
        $totalUnpaid = $totalMuzaki - $totalPaid;

        $totalUang = ZakatFitrah::where('tahun', $year)->sum('jumlah_rupiah');
        $totalBeras = ZakatFitrah::where('tahun', $year)->sum('jumlah_kg');

        $message = "📊 **Laporan Zakat Fitrah Tahun $year**\n\n";
        $message .= "👥 **Partisipasi:**\n";
        $message .= "- Total Muzaki: **$totalMuzaki** jiwa\n";
        $message .= "- Sudah Bayar: **$totalPaid** jiwa\n";
        $message .= "- Belum Bayar: **$totalUnpaid** jiwa\n\n";
        $message .= "💰 **Total Perolehan:**\n";
        $message .= "- Uang Tunai: **" . $this->formatCurrency($totalUang) . "**\n";
        $message .= "- Beras: **" . number_format($totalBeras, 1, ',', '.') . " Kg**";

        return [
            'type' => 'text',
            'message' => $message,
            'data' => [
                'total_muzaki' => $totalMuzaki,
                'paid' => $totalPaid,
                'unpaid' => $totalUnpaid,
                'money' => $totalUang,
                'rice' => $totalBeras
            ]
        ];
    }

    private function handleAsnafStats($query)
    {
        $year = Date('Y');
        $queryBuilder = \App\Models\Asnaf::where('tahun', $year);
        $catLabel = "Mustahik";

        // Filter by Category
        if (str_contains($query, 'fakir')) {
             $queryBuilder->where('kategori', 'Fakir');
             $catLabel = "Fakir";
        }
        else if (str_contains($query, 'miskin')) {
             $queryBuilder->where('kategori', 'Miskin');
             $catLabel = "Miskin";
        }

        // Filter by RT
        $rtLabel = "Semua RT";
        if (preg_match('/rt\s*(\d+)/', $query, $matches)) {
            $rtNumber = $matches[1];
            $rt = RT::where('kode', 'like', "%$rtNumber%")->first();
            if ($rt) {
                $queryBuilder->where('rt_id', $rt->id);
                $rtLabel = "RT " . $rt->kode;
            }
        }

        if (str_contains($query, 'data') || str_contains($query, 'cek') || str_contains($query, 'lihat') || str_contains($query, 'daftar')) {
            // Return List
            $data = $queryBuilder->with('rt')->orderBy('nama_kepala_keluarga')->get();
            $count = $data->count();

            $formattedData = $data->map(function($m) {
                return [
                    'nama' => $m->nama_kepala_keluarga,
                    'rt' => $m->rt ? 'RT '.$m->rt->kode : '-',
                    'status' => $m->kategori . ' (' . $m->jumlah_jiwa . ' Jiwa)'
                ];
            });

            if ($count == 0) {
                 return [
                    'type' => 'text',
                    'message' => "Mohon maaf, tidak ditemukan data **$catLabel** di **$rtLabel** pada tahun $year.",
                ];
            }

            return [
                'type' => 'list_muzaki', // Reuse frontend list type
                'message' => "Berikut data **$catLabel** di **$rtLabel** ($count KK):",
                'data' => $formattedData
            ];

        } else {
            // Return Stats Only
            $count = $queryBuilder->count();
            $jiwa = $queryBuilder->sum('jumlah_jiwa');

            return [
                'type' => 'text',
                'message' => "📊 **Data $catLabel ($rtLabel)**\n\n- Jumlah KK: **$count**\n- Total Jiwa: **$jiwa**",
                'data' => ['count' => $count, 'jiwa' => $jiwa]
            ];
        }
    }

    private function handleSantunanStats($query)
    {
        // Default target: Yatim
        $type = 'yatim';
        $label = 'Anak Yatim';

        if (str_contains($query, 'dhuafa')) {
            $type = 'dhuafa';
            $label = 'Dhuafa';
        }

        $queryBuilder = \App\Models\SantunanBeneficiary::where('jenis', $type)->where('is_active', true);
        
        $rtLabel = "Semua RT";
        if (preg_match('/rt\s*(\d+)/', $query, $matches)) {
            $rtNumber = $matches[1];
            $rt = RT::where('kode', 'like', "%$rtNumber%")->first();
            if ($rt) {
                $queryBuilder->where('rt_id', $rt->id);
                $rtLabel = "RT " . $rt->kode;
            }
        }

        $data = $queryBuilder->with('rt')->get();
        $count = $data->count();

        if (str_contains($query, 'data') || str_contains($query, 'cek') || str_contains($query, 'lihat') || str_contains($query, 'daftar') || str_contains($query, 'siapa')) {
            $formattedData = $data->map(function($m) {
                return [
                    'nama' => $m->nama_lengkap,
                    'rt' => $m->rt ? 'RT '.$m->rt->kode : '-',
                    'status' => ucfirst($m->jenis)
                ];
            });

             if ($count == 0) {
                 return [
                    'type' => 'text',
                    'message' => "Tidak ditemukan data **$label** aktif di wilayah **$rtLabel** saat ini.",
                ];
            }

            return [
                'type' => 'list_muzaki',
                'message' => "Berikut data **$label** di **$rtLabel** ($count Jiwa):",
                'data' => $formattedData
            ];
        }

        return [
            'type' => 'text',
            'message' => "💙 **Data Santunan $label ($rtLabel)**\n\nTotal Penerima Aktif: **$count Orang**",
            'data' => ['count' => $count]
        ];
    }

    private function handleSedekahStats($query)
    {
        $year = Date('Y');
        // Total Check
        $total = \App\Models\Sedekah::sum('nominal'); 
        
        // Monthly stats
        $currentMonth = Carbon::now()->isoFormat('MMMM Y');
        $totalMonth = \App\Models\Sedekah::whereMonth('tanggal', Carbon::now()->month)->whereYear('tanggal', Carbon::now()->year)->sum('nominal');

        // Recent limit
        $recent = \App\Models\Sedekah::latest()->take(5)->get();

        return [
            'type' => 'text',
            'message' => "💖 **Laporan Infak & Sedekah**\n\n- Total Terkumpul (All Time): **" . $this->formatCurrency($total) . "**\n- Bulan $currentMonth: **" . $this->formatCurrency($totalMonth) . "**",
            'data' => ['total' => $total, 'month' => $totalMonth]
        ];
    }
    
    private function formatCurrency($amount)
    {
        return "Rp " . number_format($amount, 0, ',', '.');
    }

    private function resolveUndanganRT($data)
    {
        $eventName = $data['event_name'] ?? 'Rapat Koordinasi Zakat';
        $date = $data['date'] ?? Carbon::now()->addDays(3)->isoFormat('dddd, D MMMM Y');
        $time = $data['time'] ?? '19:30 WIB';
        $location = $data['location'] ?? 'Masjid Baitulmal';

        $rts = RT::all();
        $invitees = $rts->map(function($rt) {
            return "Bapak Ketua RT " . $rt->kode . " / " . ($rt->ketua_rt ?? 'Perwakilan');
        })->toArray();

        // Using previously defined HTML structure but wrapped for helper
        $html = "
        <div style='font-family: serif; padding: 40px; line-height: 1.6;'>
            <div style='text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 30px;'>
                <h2 style='margin:0'>LEMBAGA AMIL ZAKAT BAITULMALL</h2>
                <h3 style='margin:0'>MASJID AL-IKHLAS</h3>
                <p style='margin:0'>Jl. Contoh No. 123, Kelurahan Damai, Kota Sejahtera</p>
            </div>

            <div style='text-align: right;'>
                <p>" . Carbon::now()->isoFormat('D MMMM Y') . "</p>
            </div>

            <p>Nomor: 005/BAITULMALL/II/" . date('Y') . "<br>
            Lamp: -<br>
            Hal: <strong>Undangan $eventName</strong></p>

            <p>Kepada Yth,<br>
            <strong>Bapak/Ibu Ketua RT Se-Wilayah RW 05</strong><br>
            di Tempat</p>

            <p>Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>

            <p>Puji syukur kita panjatkan ke hadirat Allah SWT. Sholawat dan salam semoga senantiasa tercurah kepada Nabi Muhammad SAW.</p>

            <p>Sehubungan dengan akan dilaksanakannya persiapan pengumpulan Zakat Fitrah tahun " . date('Y') . ", kami mengundang Bapak/Ibu untuk hadir pada:</p>

            <table style='margin-left: 20px;'>
                <tr><td width='100'>Hari/Tanggal</td><td>: <strong>$date</strong></td></tr>
                <tr><td>Waktu</td><td>: $time</td></tr>
                <tr><td>Tempat</td><td>: $location</td></tr>
                <tr><td>Agenda</td><td>: $eventName</td></tr>
            </table>

            <p>Mengingat pentingnya acara tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktunya.</p>

            <p>Demikian undangan ini kami sampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.</p>

            <p>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</p>

            <div style='margin-top: 50px; text-align: right;'>
                <p>Ketua Baitulmal,</p>
                <br><br><br>
                <p><strong>H. Ahmad Fulan</strong></p>
            </div>
        </div>
        ";

        return ['html' => $html, 'invitees' => $invitees];
    }

    private function resolveSuratTugas($data)
    {
        $activity = $data['activity'] ?? 'Pengumpulan Zakat';
        $startDate = Carbon::now()->isoFormat('D MMMM Y');
        $endDate = Carbon::now()->addDays(7)->isoFormat('D MMMM Y');

        $html = "
        <div style='font-family: serif; padding: 40px; line-height: 1.6;'>
             <div style='text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 30px;'>
                <h2 style='margin:0'>LEMBAGA AMIL ZAKAT BAITULMALL</h2>
                <h3 style='margin:0'>MASJID AL-IKHLAS</h3>
                <p style='margin:0'>Jl. Contoh No. 123, Kelurahan Damai, Kota Sejahtera</p>
            </div>

            <div style='text-align: center; margin-bottom: 20px;'>
                <h3 style='text-decoration: underline; margin: 0;'>SURAT TUGAS</h3>
                <p style='margin: 0;'>Nomor: 009/ST/BAITULMALL/II/" . date('Y') . "</p>
            </div>

            <p>Yang bertanda tangan di bawah ini:</p>
            <table style='margin-left: 20px;'>
                <tr><td width='100'>Nama</td><td>: H. Ahmad Fulan</td></tr>
                <tr><td>Jabatan</td><td>: Ketua Baitulmal</td></tr>
            </table>

            <p>Memberikan tugas kepada nama-nama di bawah ini:</p>
            <table style='margin-left: 20px; border-collapse: collapse; width: 100%;' border='1'>
                <tr style='background: #f0f0f0;'>
                    <th style='padding: 5px;'>No</th>
                    <th style='padding: 5px;'>Nama</th>
                    <th style='padding: 5px;'>Jabatan</th>
                </tr>
                <tr>
                    <td style='padding: 5px; text-align: center;'>1</td>
                    <td style='padding: 5px;'>Ust. Abdullah</td>
                    <td style='padding: 5px;'>Koordinator Lapangan</td>
                </tr>
                <tr>
                    <td style='padding: 5px; text-align: center;'>2</td>
                    <td style='padding: 5px;'>Bapak Budi Santoso</td>
                    <td style='padding: 5px;'>Amil Zakat</td>
                </tr>
            </table>

            <p>Untuk melaksanakan tugas sebagai <strong>Panitia $activity</strong> yang akan dilaksanakan pada:</p>
            <table style='margin-left: 20px;'>
                <tr><td width='100'>Tanggal</td><td>: $startDate s/d $endDate</td></tr>
                <tr><td>Lokasi</td><td>: Lingkungan RW 05</td></tr>
            </table>

            <p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>

            <div style='text-align: right; margin-top: 40px;'>
                <p>Ditetapkan di: Kota Sejahtera<br>Pada Tanggal: " . Carbon::now()->isoFormat('D MMMM Y') . "</p>
                <br>
                <p>Ketua Baitulmal,</p>
                <br><br><br>
                <p><strong>H. Ahmad Fulan</strong></p>
            </div>
        </div>
        ";

        return ['html' => $html];
    }

    /**
     * Handle Event Generation (Rundown, Budget, Checklist)
     */
    public function generateEventData(Request $request)
    {
        $type = $request->input('type'); // 'rundown', 'budget', 'checklist', 'description'
        $eventData = $request->input('data', []);

        switch ($type) {
            case 'rundown':
                return $this->generateRundown($eventData);
            case 'budget':
                return $this->generateBudget($eventData);
            case 'checklist':
                return $this->generateChecklist($eventData);
            case 'description':
                return $this->generateDescription($eventData);
            default:
                return response()->json(['error' => 'Unknown generation type'], 400);
        }
    }

    // --- Event Logic Helpers ---

    private function generateRundown($data)
    {
        $title = strtolower($data['title'] ?? '');
        $startTime = $data['startTime'] ?? '08:00';
        $startHour = (int) explode(':', $startTime)[0];
        
        $rundown = [];

        // Common Opening
        $rundown[] = [
            'time_start' => sprintf("%02d:00", $startHour),
            'time_end' => sprintf("%02d:30", $startHour),
            'activity' => 'Registrasi & Persiapan',
            'person_in_charge' => 'Panitia Penerima Tamu',
            'notes' => 'Pastikan daftar hadir siap'
        ];

        // Specific Logic based on Keyword
        if (str_contains($title, 'santunan') || str_contains($title, 'yatim') || str_contains($title, 'berbagi')) {
            $rundown[] = [
                'time_start' => sprintf("%02d:30", $startHour),
                'time_end' => sprintf("%02d:45", $startHour),
                'activity' => 'Pembukaan & Tilawah',
                'person_in_charge' => 'MC & Qori',
                'notes' => 'Ayat tentang sedekah'
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:45", $startHour),
                'time_end' => sprintf("%02d:15", $startHour + 1),
                'activity' => 'Sambutan Ketua Baitulmal',
                'person_in_charge' => 'Ketua Panitia',
                'notes' => 'Laporan penyaluran'
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:15", $startHour + 1),
                'time_end' => sprintf("%02d:00", $startHour + 2),
                'activity' => 'Prosesi Penyerahan Santunan',
                'person_in_charge' => 'Seluruh Panitia',
                'notes' => 'Diiringi sholawat'
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:00", $startHour + 2),
                'time_end' => sprintf("%02d:30", $startHour + 2),
                'activity' => 'Doa Bersama & Penutup',
                'person_in_charge' => 'Ustadz Pembina',
                'notes' => 'Doa khusus untuk donatur'
            ];

        } else if (str_contains($title, 'kajian') || str_contains($title, 'tabligh') || str_contains($title, 'pengajian')) {
            $rundown[] = [
                'time_start' => sprintf("%02d:30", $startHour),
                'time_end' => sprintf("%02d:45", $startHour),
                'activity' => 'Pembukaan',
                'person_in_charge' => 'MC',
                'notes' => ''
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:45", $startHour),
                'time_end' => sprintf("%02d:45", $startHour + 1), // 1 hour
                'activity' => 'Materi Kajian Utama',
                'person_in_charge' => 'Ustadz Pemateri',
                'notes' => 'Tema: ' . ($title ?? 'Kajian Islam')
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:45", $startHour + 1),
                'time_end' => sprintf("%02d:15", $startHour + 2),
                'activity' => 'Tanya Jawab',
                'person_in_charge' => 'Moderator',
                'notes' => 'Siapkan mic wireless'
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:15", $startHour + 2),
                'time_end' => sprintf("%02d:30", $startHour + 2),
                'activity' => 'Doa Penutup',
                'person_in_charge' => 'Ustadz Pemateri',
                'notes' => ''
            ];
            
        } else {
            // General Event Fallback
            $rundown[] = [
                'time_start' => sprintf("%02d:30", $startHour),
                'time_end' => sprintf("%02d:00", $startHour + 1),
                'activity' => 'Acara Inti',
                'person_in_charge' => 'Panitia',
                'notes' => ''
            ];
            $rundown[] = [
                'time_start' => sprintf("%02d:00", $startHour + 1),
                'time_end' => sprintf("%02d:30", $startHour + 1),
                'activity' => 'Ramah Tamah & Penutup',
                'person_in_charge' => 'Semua',
                'notes' => ''
            ];
        }

        return response()->json($rundown);
    }

    private function generateBudget($data)
    {
        $title = strtolower($data['title'] ?? '');
        $participants = (int) ($data['participants'] ?? 50);
        $items = [];

        // 1. Konsumsi (Always Calculate based on participants)
        $costPerBox = 15000; // Standard snack
        if (str_contains($title, 'buka puasa') || str_contains($title, 'makan')) {
            $costPerBox = 35000; // Heavy meal
        }
        
        $items[] = [
            'category' => 'Konsumsi',
            'item' => 'Snack/Makan Peserta (' . $participants . ' pax)',
            'quantity' => $participants,
            'unit_cost' => $costPerBox,
            'estimated_total' => $participants * $costPerBox
        ];

        // 2. Honorarium
        if (str_contains($title, 'kajian') || str_contains($title, 'tabligh')) {
            $items[] = [
                'category' => 'Honorarium',
                'item' => 'Bisyaroh Pemateri',
                'quantity' => 1,
                'unit_cost' => 500000, // Example
                'estimated_total' => 500000
            ];
        }

        // 3. Equipment depending on scale
        if (str_contains($title, 'akbar') || $participants > 100) {
            $items[] = [
                'category' => 'Perlengkapan',
                'item' => 'Sewa Tenda & Sound System Tambahan',
                'quantity' => 1,
                'unit_cost' => 2000000,
                'estimated_total' => 2000000
            ];
        } else {
            $items[] = [
                'category' => 'Kesekretariatan',
                'item' => 'Cetak Undangan & Banner',
                'quantity' => 1,
                'unit_cost' => 150000,
                'estimated_total' => 150000
            ];
        }

        // 4. Santunan specifics
        if (str_contains($title, 'santunan')) {
             $santunanPerPerson = 200000;
             $totalSantunan = $participants * $santunanPerPerson; // Assuming participants = beneficiaries for simplicity or adjust logic
             // Usually participants include committee/guests. Let's assume 50 beneficiaries fixed for now or logic needs prompt
             $beneficiaries = 30; // Estimate
             
             $items[] = [
                'category' => 'Santunan',
                'item' => 'Paket Santunan Tunai (Est. 30 Anak)',
                'quantity' => $beneficiaries,
                'unit_cost' => $santunanPerPerson,
                'estimated_total' => $beneficiaries * $santunanPerPerson
             ];
        }

        return response()->json(['items' => $items, 'notes' => 'Estimasi otomatis berdasarkan jenis acara']);
    }

    private function generateChecklist($data)
    {
        $title = strtolower($data['title'] ?? '');
        $checklist = [];
        $risks = [];

        // General Checklist
        $checklist[] = ['item' => 'Kebersihan area acara', 'priority' => 'high'];
        $checklist[] = ['item' => 'Cek sound system', 'priority' => 'high'];

        // Specifics
        if (str_contains($title, 'santunan')) {
            $checklist[] = ['item' => 'Siapkan amplop santunan', 'priority' => 'high'];
            $checklist[] = ['item' => 'Konfirmasi kehadiran anak yatim/dhuafa', 'priority' => 'high'];
            $risks[] = ['risk' => 'Peserta datang melebihi undangan', 'mitigation' => 'Siapkan cadangan paket'];
        }

        if (str_contains($title, 'kajian')) {
            $checklist[] = ['item' => 'Pastikan air minum untuk pemateri', 'priority' => 'medium'];
            $checklist[] = ['item' => 'Siapkan meja kecil di depan', 'priority' => 'low'];
            $risks[] = ['risk' => 'Pemateri berhalangan hadir', 'mitigation' => 'Siapkan pemateri cadangan dari internal'];
        }

        return response()->json(['checklist' => $checklist, 'risks' => $risks]);
    }

    private function generateDescription($data)
    {
        $title = $data['title'] ?? '[Acara]';
        $desc = "Hadirilah " . $title . " yang Insya Allah akan dilaksanakan pada tanggal " . ($data['startDate'] ?? 'nanti') . ". Mari kita ramaikan syiar Islam di lingkungan kita.";
        return response()->json(['description' => $desc]);
    }
}
