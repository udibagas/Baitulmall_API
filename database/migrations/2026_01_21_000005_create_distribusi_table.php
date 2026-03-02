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
        Schema::create('distribusi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->onDelete('set null');
            $table->string('kategori_asnaf')->comment('Fakir, Miskin, Fisabilillah, Amil');
            $table->decimal('jumlah_kg', 8, 2)->nullable();
            $table->decimal('jumlah_rupiah', 12, 2)->nullable();
            $table->date('tanggal');
            $table->year('tahun');
            $table->enum('status', ['planned', 'distributed', 'verified'])->default('planned');
            $table->string('distributed_by')->nullable()->comment('Amil name who distributed');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->index(['tahun', 'status']);
            $table->index('kategori_asnaf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi');
    }
};
