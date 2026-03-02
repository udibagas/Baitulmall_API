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
        Schema::create('zakat_produktif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asnaf_id')->constrained('asnaf')->onDelete('cascade');
            $table->string('nama_usaha');
            $table->decimal('modal_awal', 15, 2);
            $table->text('keterangan')->nullable();
            $table->date('tanggal_mulai');
            $table->enum('status', ['aktif', 'mandiri', 'gagal'])->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('zakat_produktif_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakat_produktif_id')->constrained('zakat_produktif')->onDelete('cascade');
            $table->date('tanggal_laporan');
            $table->decimal('omzet', 15, 2);
            $table->decimal('laba', 15, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_produktif_monitoring');
        Schema::dropIfExists('zakat_produktif');
    }
};
