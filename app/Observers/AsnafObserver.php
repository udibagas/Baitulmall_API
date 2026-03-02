<?php

namespace App\Observers;

use App\Models\Asnaf;
use App\Events\MustahikUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AsnafObserver
{
    public function created(Asnaf $asnaf): void
    {
        $this->clearCacheAndBroadcast($asnaf->tahun);
    }

    public function updated(Asnaf $asnaf): void
    {
        $this->clearCacheAndBroadcast($asnaf->tahun);
    }

    public function deleted(Asnaf $asnaf): void
    {
        $this->clearCacheAndBroadcast($asnaf->tahun);
    }

    public function clearCacheAndBroadcast($tahun): void
    {
        Log::debug("Observer: clearCacheAndBroadcast (Asnaf) for year: {$tahun}");
        
        try {
            // Dashboard Main Stats
            Cache::forget("dashboard_stats_summary_{$tahun}");
            
            // Asnaf Management Stats
            Cache::forget("asnaf_stats_summary_{$tahun}_all");
            
            // Public Site Performance Aggregation
            Cache::forget("public_stats_aggregation_v2");
            Cache::forget("public_live_stats");
            
            Log::info("Asnaf-related Caches Cleared: {$tahun}");

            broadcast(new MustahikUpdated($tahun));
        } catch (\Exception $e) {
            Log::error("AsnafObserver Error: " . $e->getMessage());
        }
    }
}
