<?php

namespace App\Observers;

use App\Models\Sedekah;
use App\Events\DashboardUpdated;
use Illuminate\Support\Facades\Cache;

class SedekahObserver
{
    public function created(Sedekah $sedekah): void
    {
        $this->clearCacheAndBroadcast($sedekah->tahun);
    }

    public function updated(Sedekah $sedekah): void
    {
        $this->clearCacheAndBroadcast($sedekah->tahun);
    }

    public function deleted(Sedekah $sedekah): void
    {
        $this->clearCacheAndBroadcast($sedekah->tahun);
    }

    protected function clearCacheAndBroadcast(int $tahun): void
    {
        Cache::forget("dashboard_stats_summary_{$tahun}");
        Cache::forget("sedekah_summary_{$tahun}_all_all");
        
        Cache::forget("public_stats_aggregation_v2");
        Cache::forget("public_live_stats");
        
        event(new DashboardUpdated());
    }
}
