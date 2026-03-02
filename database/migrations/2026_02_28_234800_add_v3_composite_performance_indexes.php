<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Composite indexes for common query patterns in Asnaf
        DB::statement('CREATE INDEX IF NOT EXISTS asnaf_rt_tahun_status_index ON asnaf (rt_id, tahun, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS asnaf_kategori_tahun_status_index ON asnaf (kategori, tahun, status)');
        
        // Indexes for Distribusi and Zakat Fitrah
        DB::statement('CREATE INDEX IF NOT EXISTS distribusi_asnaf_tahun_index ON distribusi (asnaf_id, tahun)');
        DB::statement('CREATE INDEX IF NOT EXISTS zakat_fitrah_tahun_rt_index ON zakat_fitrah (tahun, rt_id)');
        
        // Index for performance in people and assignments
        DB::statement('CREATE INDEX IF NOT EXISTS assignments_structure_status_index ON assignments (structure_id, status)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS asnaf_rt_tahun_status_index');
        DB::statement('DROP INDEX IF EXISTS asnaf_kategori_tahun_status_index');
        DB::statement('DROP INDEX IF EXISTS distribusi_asnaf_tahun_index');
        DB::statement('DROP INDEX IF EXISTS zakat_fitrah_tahun_rt_index');
        DB::statement('DROP INDEX IF EXISTS assignments_structure_status_index');
    }
};
