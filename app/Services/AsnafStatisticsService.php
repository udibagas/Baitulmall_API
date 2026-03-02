<?php

namespace App\Services;

use App\Models\Asnaf;
use App\Models\RT;
use Illuminate\Support\Facades\DB;

class AsnafStatisticsService
{
    /**
     * Get count by kategori for a given year
     *
     * @param int $tahun
     * @return array
     */
    public function getCountByKategori(int $tahun, ?int $bulan = null): array
    {
        $query = Asnaf::where('tahun', $tahun)->where('status', 'active');
        if ($bulan) {
            $query->whereMonth('created_at', $bulan);
        }

        $stats = $query->selectRaw('kategori, COUNT(*) as jumlah, SUM(jumlah_jiwa) as total_jiwa')
            ->groupBy('kategori')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->kategori] = [
                'jumlah_kk' => $stat->jumlah,
                'jumlah_jiwa' => $stat->total_jiwa,
            ];
        }

        return $result;
    }

    /**
     * Get count by RT for a given year
     *
     * @param int $tahun
     * @return array
     */
    public function getCountByRT(int $tahun, ?int $bulan = null): array
    {
        $query = Asnaf::join('rts', 'asnaf.rt_id', '=', 'rts.id')
            ->where('asnaf.tahun', $tahun)
            ->where('asnaf.status', 'active');
            
        if ($bulan) {
            $query->whereMonth('asnaf.created_at', $bulan);
        }

        $stats = $query->selectRaw('rts.kode, COUNT(*) as jumlah, SUM(asnaf.jumlah_jiwa) as total_jiwa')
            ->groupBy('rts.kode')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[$stat->kode] = [
                'jumlah_kk' => $stat->jumlah,
                'jumlah_jiwa' => $stat->total_jiwa,
            ];
        }

        return $result;
    }

    /**
     * Get map data with coordinates for visualization
     *
     * @param int $tahun
     * @param string|null $kategori Filter by category
     * @param int|null $rtId Filter by RT
     * @return array
     */
    public function getMapData(int $tahun, ?string $kategori = null, ?int $rtId = null): array
    {
        $query = Asnaf::with('rt:id,kode,rw')
            ->where('tahun', $tahun)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        if ($rtId) {
            $query->where('rt_id', $rtId);
        }

        $asnaf = $query->get();

        return $asnaf->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'kategori' => $item->kategori,
                'rt' => $item->rt->kode,
                'jumlah_jiwa' => $item->jumlah_jiwa,
                'latitude' => (float) $item->latitude,
                'longitude' => (float) $item->longitude,
            ];
        })->toArray();
    }

    /**
     * Get count by RT and Category for a given year
     *
     * @param int $tahun
     * @return array
     */
    public function getCountByRTAndKategori(int $tahun, ?int $bulan = null): array
    {
        $query = Asnaf::join('rts', 'asnaf.rt_id', '=', 'rts.id')
            ->where('asnaf.tahun', $tahun)
            ->where('asnaf.status', 'active');
            
        if ($bulan) {
            $query->whereMonth('asnaf.created_at', $bulan);
        }

        $stats = $query->selectRaw('rts.kode as rt, asnaf.kategori, COUNT(*) as jumlah_kk, SUM(asnaf.jumlah_jiwa) as jumlah_jiwa')
            ->groupBy('rts.kode', 'asnaf.kategori')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            if (!isset($result[$stat->rt])) {
                $result[$stat->rt] = [];
            }
            $result[$stat->rt][$stat->kategori] = [
                'jumlah_kk' => $stat->jumlah_kk,
                'jumlah_jiwa' => $stat->jumlah_jiwa,
            ];
        }

        // Ensure RTs are sorted
        ksort($result);

        return $result;
    }

    /**
     * Get overall statistics summary with caching
     */
    public function getOverallSummary(int $tahun, ?int $bulan = null): array
    {
        $cacheKey = "asnaf_stats_summary_{$tahun}_" . ($bulan ?? 'all');

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tahun, $bulan) {
            $query = Asnaf::where('tahun', $tahun)->where('status', 'active');
            if ($bulan) {
                $query->whereMonth('created_at', $bulan);
            }
            
            $total = $query->selectRaw('COUNT(*) as total_kk, SUM(jumlah_jiwa) as total_jiwa')->first();

            return [
                'total_kk' => $total->total_kk ?? 0,
                'total_jiwa' => $total->total_jiwa ?? 0,
                'by_kategori' => $this->getCountByKategori($tahun, $bulan),
                'by_rt' => $this->getCountByRT($tahun, $bulan),
                'by_rt_kategori' => $this->getCountByRTAndKategori($tahun, $bulan),
                '_cached_at' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * Get RT list with Asnaf count
     *
     * @return array
     */
    public function getRTsWithAsnafCount(int $tahun): array
    {
        return RT::withCount(['asnaf' => function ($query) use ($tahun) {
            $query->where('tahun', $tahun)->where('status', 'active');
        }])
        ->get()
        ->map(function ($rt) {
            return [
                'id' => $rt->id,
                'kode' => $rt->kode,
                'rw' => $rt->rw,
                'ketua' => $rt->ketua,
                'jumlah_asnaf' => $rt->asnaf_count,
            ];
        })
        ->toArray();
    }

    /**
     * Get Graduation Index (Social Mobility) comparing current year and previous year
     *
     * @param int $tahun Current year
     * @return array
     */
    public function getGraduationIndex(int $tahun): array
    {
        $cacheKey = "asnaf_graduation_index_{$tahun}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(1), function () use ($tahun) {
            $prevTahun = $tahun - 1;

            $currentAsnaf = Asnaf::with('rt:id,kode')
                ->select(['id', 'rt_id', 'nama', 'kategori', 'score', 'tahun'])
                ->where('tahun', $tahun)
                ->where('status', 'active')
                ->get();

            $prevAsnaf = Asnaf::with('rt:id,kode')
                ->select(['id', 'rt_id', 'nama', 'kategori', 'score', 'tahun'])
                ->where('tahun', $prevTahun)
                ->where('status', 'active')
                ->get();

            // Use keyed collection for $O(1)$ lookup
            $prevMap = $prevAsnaf->keyBy(fn($item) => $item->rt_id . '_' . strtolower(trim($item->nama)));
            $currentMap = $currentAsnaf->keyBy(fn($item) => $item->rt_id . '_' . strtolower(trim($item->nama)));

            $graduated = [];
            $improved = [];
            $declined = [];
            $stagnant = [];

            foreach ($prevMap as $key => $prev) {
                if (!$currentMap->has($key)) {
                    if (in_array($prev->kategori, ['Fakir', 'Miskin'])) {
                        $graduated[] = [
                            'nama' => $prev->nama,
                            'rt' => $prev->rt->kode ?? '??',
                            'alasan' => 'Tidak lagi terdaftar sebagai Mustahik (Mandiri)',
                            'prev_kategori' => $prev->kategori,
                            'prev_score' => $prev->score,
                            'current_kategori' => '-',
                            'current_score' => null,
                            'delta_score' => null
                        ];
                    }
                } else {
                    $curr = $currentMap[$key];
                    $deltaScore = ($curr->score ?? 0) - ($prev->score ?? 0);
                    $isPoorBefore = in_array($prev->kategori, ['Fakir', 'Miskin']);
                    $isPoorNow = in_array($curr->kategori, ['Fakir', 'Miskin']);

                    $baseData = [
                        'id' => $curr->id,
                        'nama' => $curr->nama,
                        'rt' => $curr->rt->kode ?? '??',
                        'prev_kategori' => $prev->kategori,
                        'prev_score' => $prev->score,
                        'current_kategori' => $curr->kategori,
                        'current_score' => $curr->score,
                        'delta_score' => $deltaScore
                    ];

                    if ($isPoorBefore && !$isPoorNow && in_array($curr->kategori, ['Mualaf', 'Ibnu Sabil', 'Fisabilillah', 'Amil', 'Gharim'])) {
                        $baseData['alasan'] = 'Pindah ke kategori ' . $curr->kategori;
                        $graduated[] = $baseData;
                    } elseif ($deltaScore > 0) {
                        $improved[] = $baseData;
                    } elseif ($deltaScore < 0) {
                        $declined[] = $baseData;
                    } else {
                        $stagnant[] = $baseData;
                    }
                }
            }

            return [
                'tahun' => $tahun,
                'summary' => [
                    'total_evaluated' => count($prevMap),
                    'graduated' => count($graduated),
                    'improved' => count($improved),
                    'declined' => count($declined),
                    'stagnant' => count($stagnant)
                ],
                'details' => [
                    'graduated' => $graduated,
                    'improved' => $improved,
                    'declined' => $declined,
                    'stagnant' => $stagnant
                ],
                '_cached_at' => now()->toDateTimeString(),
            ];
        });
    }

    /**
     * Clear statistics cache for a specific year
     */
    public function clearCache(int $tahun): void
    {
        \Illuminate\Support\Facades\Cache::forget("asnaf_stats_summary_{$tahun}_all");
        \Illuminate\Support\Facades\Cache::forget("asnaf_graduation_index_{$tahun}");
        \Illuminate\Support\Facades\Log::info("Asnaf Cache Cleared for year $tahun");
    }
}
