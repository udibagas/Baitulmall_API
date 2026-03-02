<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL for idempotency as some indexes might have been partially created
        // SQLite supports CREATE INDEX IF NOT EXISTS
        
        DB::statement('CREATE INDEX IF NOT EXISTS asnaf_tahun_status_index ON asnaf (tahun, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS sedekah_tanggal_index ON sedekah (tanggal)');
        DB::statement('CREATE INDEX IF NOT EXISTS muzaki_tanggal_bayar_index ON muzaki (tanggal_bayar)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS asnaf_tahun_status_index');
        DB::statement('DROP INDEX IF EXISTS sedekah_tanggal_index');
        DB::statement('DROP INDEX IF EXISTS muzaki_tanggal_bayar_index');
    }
};
