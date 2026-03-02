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
        Schema::create('santunan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_anak');
            $table->foreignId('rt_id')->constrained('rts');
            $table->decimal('besaran', 12, 2)->comment('Amount per child');
            $table->enum('status_penerimaan', ['sudah', 'belum'])->default('belum');
            $table->date('tanggal_distribusi')->nullable();
            $table->year('tahun');
            $table->timestamps();
            
            $table->index(['tahun', 'status_penerimaan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('santunan');
    }
};
