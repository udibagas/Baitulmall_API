<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Correspondence;
use App\Models\RT;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CorrespondenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $letters = Correspondence::orderBy('created_at', 'desc')->get();
        return response()->json($letters);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_surat' => 'required|string',
            'perihal' => 'required|string',
            'isi_surat' => 'required|string',
            'tanggal_surat' => 'required|date',
        ]);

        // Auto Generate Number if empty (Simple logic)
        $year = date('Y');
        $count = Correspondence::whereYear('created_at', $year)->count() + 1;
        $nomor = sprintf("%03d/BAITULMALL/%s/%s", $count, date('m'), $year);

        $letter = Correspondence::create(array_merge($validated, [
            'nomor_surat' => $request->input('nomor_surat', $nomor),
            'tujuan' => $request->input('tujuan'),
            'status' => 'final'
        ]));

        return response()->json($letter, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Correspondence::findOrFail($id);
    }

    /**
     * Generate Draft using AI (Rule-based)
     */
    public function generate(Request $request)
    {
        $type = $request->input('type'); // 'undangan', 'tugas'
        $topic = $request->input('topic', 'Kegiatan Rutin');
        $dateStr = $request->input('date'); 

        // Date Logic
        if ($dateStr) {
             $date = Carbon::parse($dateStr)->isoFormat('dddd, D MMMM Y');
        } else {
             $date = Carbon::now()->addDays(3)->isoFormat('dddd, D MMMM Y');
        }

        if ($type === 'undangan') {
            return $this->resolveUndanganRT(['event_name' => $topic, 'date' => $date]);
        } 
        else if ($type === 'tugas') {
            return $this->resolveSuratTugas(['activity' => $topic]);
        }

        return response()->json(['error' => 'Unknown template type'], 400);
    }

    // --- Private Helpers (Moved from SmartAssistant) ---

    private function resolveUndanganRT($data)
    {
        $eventName = $data['event_name'];
        $date = $data['date'];
        $time = '19:30 WIB';
        $location = 'Masjid Baitulmall';

        $rts = RT::all();
        $invitees = $rts->map(function($rt) {
            return "Bapak Ketua RT " . $rt->kode . " / " . ($rt->ketua_rt ?? 'Perwakilan');
        })->toArray();

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

            <p>Nomor: [AUTO]/BAITULMALL/II/" . date('Y') . "<br>
            Lamp: -<br>
            Hal: <strong>Undangan $eventName</strong></p>

            <p>Kepada Yth,<br>
            <strong>Bapak/Ibu Ketua RT Se-Wilayah RW 05</strong><br>
            di Tempat</p>

            <p>Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>
            <p>Sehubungan dengan akan dilaksanakannya <strong>$eventName</strong>, kami mengundang Bapak/Ibu untuk hadir pada:</p>

            <table style='margin-left: 20px;'>
                <tr><td width='100'>Hari/Tanggal</td><td>: <strong>$date</strong></td></tr>
                <tr><td>Waktu</td><td>: $time</td></tr>
                <tr><td>Tempat</td><td>: $location</td></tr>
                <tr><td>Agenda</td><td>: $eventName</td></tr>
            </table>

            <p>Demikian undangan ini kami sampaikan, atas perhatian dan kehadirannya kami ucapkan terima kasih.</p>
            <p>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</p>

            <div style='margin-top: 50px; text-align: right;'>
                <p>Ketua Baitulmall,</p>
                <br><br><br>
                <p><strong>H. Ahmad Fulan</strong></p>
            </div>
        </div>
        ";

        return response()->json(['html' => $html, 'invitees' => $invitees]);
    }

    private function resolveSuratTugas($data)
    {
        $activity = $data['activity'];
        $startDate = Carbon::now()->isoFormat('D MMMM Y');

        $html = "
        <div style='font-family: serif; padding: 40px; line-height: 1.6;'>
             <div style='text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 30px;'>
                <h2 style='margin:0'>LEMBAGA AMIL ZAKAT BAITULMALL</h2>
                <h3 style='margin:0'>MASJID AL-IKHLAS</h3>
            </div>

            <div style='text-align: center; margin-bottom: 20px;'>
                <h3 style='text-decoration: underline; margin: 0;'>SURAT TUGAS</h3>
                <p style='margin: 0;'>Nomor: [AUTO]/ST/BAITULMALL/" . date('Y') . "</p>
            </div>

            <p>Yang bertanda tangan di bawah ini:</p>
            <table style='margin-left: 20px;'>
                <tr><td width='100'>Nama</td><td>: H. Ahmad Fulan</td></tr>
                <tr><td>Jabatan</td><td>: Ketua Baitulmall</td></tr>
            </table>

            <p>Memberikan tugas kepada tim untuk melaksanakan:</p>
            <h4 style='text-align:center'>$activity</h4>

            <p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>

            <div style='text-align: right; margin-top: 40px;'>
                <p>Ditetapkan di: Kota Sejahtera<br>Pada Tanggal: " . Carbon::now()->isoFormat('D MMMM Y') . "</p>
                <br><br><br>
                <p><strong>H. Ahmad Fulan</strong></p>
            </div>
        </div>
        ";

        return response()->json(['html' => $html]);
    }

    public function exportToGoogleDoc(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Google Docs export is not available in this environment.',
        ], 503);
    }
}
