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
            $table->enum('status_rumah_detail', ['milik_layak', 'milik_tak_layak', 'sewa', 'numpang'])->nullable()->after('kondisi_rumah');
            $table->enum('kondisi_bangunan', ['permanen_baik', 'semi_permanen', 'tidak_permanen'])->nullable()->after('status_rumah_detail');
            $table->enum('fasilitas_dasar', ['layak', 'salah_satu_terbatas', 'keduanya_terbatas'])->nullable()->after('kondisi_bangunan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asnaf', function (Blueprint $table) {
            $table->dropColumn(['status_rumah_detail', 'kondisi_bangunan', 'fasilitas_dasar']);
        });
    }
};
