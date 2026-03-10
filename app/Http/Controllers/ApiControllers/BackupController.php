<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\Person;
use App\Models\OrganizationStructure;
use App\Models\Assignment;
use App\Models\RT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    /**
     * Export Asnaf data to JSON
     */
    public function exportAsnaf()
    {
        $data = Asnaf::all();
        $filename = 'asnaf_backup_' . date('Y-m-d_H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo $data->toJson(JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import Asnaf data from JSON
     */
    public function importAsnaf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json'
        ]);

        try {
            $json = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return response()->json(['success' => false, 'message' => 'Invalid JSON format'], 400);
            }

            DB::beginTransaction();

            // Optional: Backup current state or truncate?
            // For safety, we use updateOrCreate based on name and year
            $count = 0;
            foreach ($data as $item) {
                // Remove ID to let DB generate new ones if needed, 
                // but usually backups keep IDs if it's a full restore.
                // Here we keep it flexible.
                $identifier = [
                    'nama' => $item['nama'],
                    'tahun' => $item['tahun'],
                    'rt_id' => $item['rt_id']
                ];

                Asnaf::updateOrCreate($identifier, array_diff_key($item, array_flip(['id', 'created_at', 'updated_at'])));
                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported $count Asnaf records."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Asnaf Import Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export SDM data (Structure, People, Assignments)
     */
    public function exportSDM()
    {
        $data = [
            'structures' => OrganizationStructure::all(),
            'people' => Person::all(),
            'assignments' => Assignment::all()
        ];

        $filename = 'sdm_backup_' . date('Y-m-d_H-i-s') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import SDM data
     */
    public function importSDM(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json'
        ]);

        try {
            $json = file_get_contents($request->file('file')->getRealPath());
            $data = json_decode($json, true);

            if (!isset($data['structures']) || !isset($data['people']) || !isset($data['assignments'])) {
                return response()->json(['success' => false, 'message' => 'Invalid SDM backup format'], 400);
            }

            DB::beginTransaction();

            $structCount = 0;
            $personCount = 0;
            $assignCount = 0;

            // 1. Structures
            foreach ($data['structures'] as $item) {
                OrganizationStructure::updateOrCreate(
                    ['kode_struktur' => $item['kode_struktur']],
                    array_diff_key($item, array_flip(['id', 'created_at', 'updated_at']))
                );
                $structCount++;
            }

            // 2. People
            foreach ($data['people'] as $item) {
                Person::updateOrCreate(
                    ['nama_lengkap' => $item['nama_lengkap'], 'no_wa' => $item['no_wa']],
                    array_diff_key($item, array_flip(['id', 'created_at', 'updated_at']))
                );
                $personCount++;
            }

            // 3. Assignments (Tricky due to IDs)
            // We might need to map old IDs to new IDs if we don't force ID preservation.
            // For a simple backup/restore, if we assume the RT/Structure codes are stable, 
            // we can try to re-link by unique attributes.
            
            // Re-fetch maps
            $structureMap = OrganizationStructure::pluck('id', 'kode_struktur')->toArray();
            $peopleMap = Person::all()->mapWithKeys(function($p) {
                return [$p->nama_lengkap . '|' . $p->no_wa => $p->id];
            })->toArray();

            foreach ($data['assignments'] as $item) {
                // Find matching structure and person from the backup data context
                $origStruct = collect($data['structures'])->firstWhere('id', $item['structure_id']);
                $origPerson = collect($data['people'])->firstWhere('id', $item['person_id']);

                if ($origStruct && $origPerson) {
                    $newStructId = $structureMap[$origStruct['kode_struktur']] ?? null;
                    $newPersonKey = $origPerson['nama_lengkap'] . '|' . $origPerson['no_wa'];
                    $newPersonId = $peopleMap[$newPersonKey] ?? null;

                    if ($newStructId && $newPersonId) {
                        Assignment::updateOrCreate(
                            [
                                'person_id' => $newPersonId,
                                'structure_id' => $newStructId,
                                'jabatan' => $item['jabatan']
                            ],
                            array_diff_key($item, array_flip(['id', 'person_id', 'structure_id', 'created_at', 'updated_at']))
                        );
                        $assignCount++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Imported: $structCount structures, $personCount people, $assignCount assignments."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SDM Import Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
