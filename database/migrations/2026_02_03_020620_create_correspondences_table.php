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
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->nullable();
            $table->string('jenis_surat'); // 'Undangan', 'Surat Tugas', 'Berita Acara', etc.
            $table->string('perihal')->nullable();
            $table->string('tujuan')->nullable();
            $table->longText('isi_surat'); // HTML Content
            $table->date('tanggal_surat');
            $table->string('status')->default('draft'); // draft, final, archived
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
