<?php

namespace App\Observers;

use App\Models\Muzaki;
use App\Events\DashboardUpdated;
use Illuminate\Support\Facades\Cache;

class MuzakiObserver
{
    public function created(Muzaki $muzaki): void
    {
        $this->clearCacheAndBroadcast($muzaki->tahun);
    }

    public function updated(Muzaki $muzaki): void
    {
        $this->clearCacheAndBroadcast($muzaki->tahun);
    }

    public function deleted(Muzaki $muzaki): void
    {
        $this->clearCacheAndBroadcast($muzaki->tahun);
    }

    protected function clearCacheAndBroadcast(int $tahun): void
    {
        Cache::forget("dashboard_stats_summary_{$tahun}");
        Cache::forget("zakat_fitrah_stats_{$tahun}");
        Cache::forget("zakat_fitrah_summary_{$tahun}_all");
        Cache::forget("muzaki_stats_{$tahun}");
        
        Cache::forget("public_stats_aggregation_v2");
        Cache::forget("public_live_stats");
        
        event(new DashboardUpdated());
    }
}
