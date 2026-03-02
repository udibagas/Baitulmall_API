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
        Schema::table('asnaf', function (Blueprint $table) {
            $table->index(['nama', 'kategori']);
            $table->index('rt_id');
        });

        Schema::table('zakat_fitrah', function (Blueprint $table) {
            $table->index(['tahun', 'rt_id']);
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->index(['kategori', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asnaf', function (Blueprint $table) {
            $table->dropIndex(['nama', 'kategori']);
            $table->dropIndex(['rt_id']);
        });

        Schema::table('zakat_fitrah', function (Blueprint $table) {
            $table->dropIndex(['tahun', 'rt_id']);
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->dropIndex(['kategori', 'tanggal']);
        });
    }
};
