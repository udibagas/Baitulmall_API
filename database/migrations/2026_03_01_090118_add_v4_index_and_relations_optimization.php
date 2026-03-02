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
        // Muzaki optimizations
        DB::statement('CREATE INDEX IF NOT EXISTS muzaki_tahun_rt_id_index ON muzaki (tahun, rt_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS muzaki_status_bayar_index ON muzaki (status_bayar)');

        // Other distributions and transactions
        DB::statement('CREATE INDEX IF NOT EXISTS distribusi_tahun_status_index ON distribusi (tahun, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS sedekah_tanggal_jenis_index ON sedekah (tanggal, jenis)');

        // Search optimization for people
        DB::statement('CREATE INDEX IF NOT EXISTS people_nama_lengkap_index ON people (nama_lengkap)');
        DB::statement('CREATE INDEX IF NOT EXISTS people_rt_id_index ON people (rt_id)');

        // Ensure standard foreign key indexes exist
        if (Schema::hasTable('assignments')) {
            DB::statement('CREATE INDEX IF NOT EXISTS assignments_person_id_index ON assignments (person_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muzaki', function (Blueprint $table) {
            try { $table->dropIndex(['tahun', 'rt_id']); } catch (\Exception $e) {}
            try { $table->dropIndex(['status_bayar']); } catch (\Exception $e) {}
        });

        Schema::table('distribusi', function (Blueprint $table) {
            try { $table->dropIndex(['tahun', 'status']); } catch (\Exception $e) {}
        });

        Schema::table('sedekah', function (Blueprint $table) {
            try { $table->dropIndex(['tanggal', 'jenis']); } catch (\Exception $e) {}
        });

        Schema::table('people', function (Blueprint $table) {
            try { $table->dropIndex(['nama_lengkap']); } catch (\Exception $e) {}
            try { $table->dropIndex(['rt_id']); } catch (\Exception $e) {}
        });

        if (Schema::hasTable('assignments')) {
            Schema::table('assignments', function (Blueprint $table) {
                try { $table->dropIndex(['person_id']); } catch (\Exception $e) {}
            });
        }
    }
};
