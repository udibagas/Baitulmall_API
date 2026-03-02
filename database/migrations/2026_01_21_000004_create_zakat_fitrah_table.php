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
        Schema::create('zakat_fitrah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('muzaki_id')->constrained('muzaki')->onDelete('cascade');
            $table->foreignId('rt_id')->constrained('rts');
            $table->integer('jumlah_jiwa')->unsigned();
            $table->decimal('jumlah_kg', 8, 2);
            $table->decimal('jumlah_rupiah', 12, 2)->default(0)->comment('If payment in cash');
            $table->enum('jenis_bayar', ['beras', 'uang', 'kombinasi']);
            $table->date('tanggal');
            $table->year('tahun');
            $table->text('catatan')->nullable();
            $table->timestamps();
            
            $table->index(['tahun', 'tanggal']);
            $table->index('rt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_fitrah');
    }
};
