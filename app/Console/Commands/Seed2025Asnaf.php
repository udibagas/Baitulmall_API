<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asnaf;

class Seed2025Asnaf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:2025';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed 2025 Asnaf Data for Graduation testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting 2025 data generation...');
        $count = 0;
        $tahun2026 = Asnaf::where('tahun', 2026)->get();

        foreach($tahun2026 as $a) {
            $new = $a->replicate();
            $new->id = null;
            $new->tahun = 2025;
            
            // Basic score adjustment
            $delta = rand(-15, 15);
            $new->score = min(100, max(0, $a->score - $delta));
            $new->kategori = $a->kategori;

            // 1. Simulate "Graduation": Some who are Amil/Fisabilillah in 2026 used to be Miskin in 2025
            if (in_array($a->kategori, ['Amil', 'Fisabilillah']) && rand(1, 100) <= 30) {
                 $new->kategori = 'Miskin';
                 $new->score = rand(60, 75);
            }
            
            // 2. Simulate "Declined (New Poor)": Some who are Miskin/Fakir in 2026 used to be non-poor (Fisabilillah) in 2025
            if (in_array($a->kategori, ['Miskin', 'Fakir']) && rand(1, 100) <= 5) {
                 $new->kategori = 'Fisabilillah';
                 $new->score = rand(40, 59);
            }

            // 3. For anyone who is poor in 2025, ensure their score aligns with Fakir (>=80) or Miskin (60-79)
            if (in_array($new->kategori, ['Fakir', 'Miskin'])) {
                 // Force score to remain in poor bracket if it drifted out
                 if ($new->score < 60) {
                     $new->score = rand(60, 85); 
                 }
                 $new->kategori = ($new->score >= 80) ? 'Fakir' : 'Miskin';
            } else {
                 // Force score below 60 for non-poor
                 if ($new->score >= 60) {
                     $new->score = rand(30, 59);
                 }
            }
            
            $new->save();
            $count++;
        }
        
        $this->info("Successfully generated $count records for 2025.");
    }
}
