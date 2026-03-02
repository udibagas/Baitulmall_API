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
        Schema::create('muzaki', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts')->onDelete('cascade');
            $table->string('nama');
            $table->integer('jumlah_jiwa')->unsigned();
            $table->decimal('jumlah_beras_kg', 8, 2)->comment('Calculated or manual beras amount');
            $table->enum('status_bayar', ['lunas', 'belum', 'cicil'])->default('belum');
            $table->year('tahun');
            $table->date('tanggal_bayar')->nullable();
            $table->timestamps();
            
            $table->index(['rt_id', 'tahun', 'status_bayar']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muzaki');
    }
};
