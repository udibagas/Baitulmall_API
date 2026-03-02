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
        Schema::table('zakat_malls', function (Blueprint $table) {
            if (!Schema::hasColumn('zakat_malls', 'nama_muzaki')) {
                $table->string('nama_muzaki')->nullable()->after('rt_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zakat_malls', function (Blueprint $table) {
            if (Schema::hasColumn('zakat_malls', 'nama_muzaki')) {
                $table->dropColumn('nama_muzaki');
            }
        });
    }
};
