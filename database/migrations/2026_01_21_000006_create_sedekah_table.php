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
        Schema::create('sedekah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amil_id')->nullable()->constrained('asnaf')->onDelete('set null')->comment('Amil who collected');
            $table->foreignId('rt_id')->nullable()->constrained('rts');
            $table->decimal('jumlah', 12, 2);
            $table->enum('jenis', ['penerimaan', 'penyaluran']);
            $table->string('tujuan')->nullable()->comment('For penyaluran: destination/purpose');
            $table->date('tanggal');
            $table->year('tahun');
            $table->timestamps();
            
            $table->index(['tahun', 'jenis']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedekah');
    }
};
