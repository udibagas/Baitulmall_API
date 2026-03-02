<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Santunan;
use App\Models\Sedekah; // Use Sedekah instead
use App\Models\Asnaf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeathEventController extends Controller
{
    /**
     * Handle a reported death.
     * 1. Reduces Sedekah funds by creating a 'penyaluran' record in Sedekah table.
     * 2. Updates Asnaf data if applicable.
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string', // Name of deceased
            'rt_id' => 'required|exists:rts,id',
            'amount' => 'required|numeric', // Santunan amount
            'tanggal' => 'required|date',
            'asnaf_id' => 'nullable|exists:asnaf,id', // Optional: link to existing Asnaf ID
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $tahun = date('Y', strtotime($request->tanggal));
            $requestedAmount = $request->amount;

            // 0. CHECK SALDO SEDEKAH
            // Calculation: Total Penerimaan - Total Penyaluran
            $totalPenerimaan = Sedekah::where('tahun', $tahun)->where('jenis', 'penerimaan')->sum('jumlah');
            $totalPenyaluran = Sedekah::where('tahun', $tahun)->where('jenis', 'penyaluran')->sum('jumlah');
            $currentSaldo = $totalPenerimaan - $totalPenyaluran;

            if ($requestedAmount > $currentSaldo) {
                return response()->json([
                    'message' => 'Saldo Sedekah tidak mencukupi untuk santunan ini.',
                    'details' => [
                        'saldo_saat_ini' => $currentSaldo,
                        'jumlah_diminta' => $requestedAmount
                    ]
                ], 422);
            }

            // 1. Create Sedekah Expenditure (Penyaluran)
            // We record this as an expense in the Sedekah table
            $sedekah = Sedekah::create([
                'rt_id' => $request->rt_id,
                'jumlah' => $requestedAmount,
                'jenis' => 'penyaluran',
                'tujuan' => 'Santunan Kematian: ' . $request->nama . ' (Alm/Almh)',
                'tanggal' => $request->tanggal,
                'tahun' => $tahun,
                // 'amil_id' could be set if we tracked the logged in user, but currently API is token based without user context in this scope easily visible
                // 'nama_donatur' & 'no_hp_donatur' are irrelevant for expenses usually, or could be used to store recipient info? 
                // Better stick to 'tujuan' for description.
            ]);

            // 2. Update Asnaf Data (if linked)
            $asnafUpdateLog = null;
            if ($request->asnaf_id) {
                $asnaf = Asnaf::lockForUpdate()->find($request->asnaf_id);
                
                if ($asnaf) {
                    $originalJiwa = $asnaf->jumlah_jiwa;
                    
                    // Logic: Reduce family members
                    if ($asnaf->jumlah_jiwa > 0) {
                        $asnaf->decrement('jumlah_jiwa');
                    }

                    // Logic: If family members reach 0, mark as inactive
                    if ($asnaf->jumlah_jiwa <= 0) {
                        $asnaf->status = 'inactive';
                        $asnaf->save();
                        $asnafUpdateLog = "Asnaf {$asnaf->nama} inactivated (0 active members).";
                    } else {
                        $asnafUpdateLog = "Asnaf {$asnaf->nama} family size reduced from {$originalJiwa} to {$asnaf->jumlah_jiwa}.";
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Laporan kematian berhasil diproses (Sumber Dana: Sedekah).',
                'data' => [
                    'sedekah_transaksi' => $sedekah,
                    'asnaf_update' => $asnafUpdateLog
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses laporan kematian.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
