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
        // 1. Create new table with expanded Enum
        Schema::create('asnaf_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts')->onDelete('cascade');
            $table->string('nama');
            // Expanded Enum
            $table->enum('kategori', [
                'Fakir', 'Miskin', 'Amil', 'Mualaf', 'Riqab', 'Gharim', 'Fisabilillah', 'Ibnu Sabil'
            ]);
            $table->integer('jumlah_jiwa')->unsigned();
            $table->year('tahun');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_wa')->nullable(); // From 2nd migration
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['rt_id', 'kategori', 'tahun']);
            $table->index('status');
        });

        // 2. Copy Data
        $oldData = DB::table('asnaf')->get();
        foreach ($oldData as $row) {
            DB::table('asnaf_new')->insert((array) $row);
        }

        // 3. Drop Old
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS asnaf CASCADE');
        } else {
            Schema::dropIfExists('asnaf');
        }

        // 4. Rename New
        Schema::rename('asnaf_new', 'asnaf');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to restricted enum if necessary (Not recommended to automate data loss)
        // For safety, we just allow the expanded one in rollback or do nothing
    }
};
