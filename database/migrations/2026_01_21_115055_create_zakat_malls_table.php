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
        Schema::create('zakat_malls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts')->onDelete('cascade');
            $table->string('kategori'); // Perdagangan, Profesi, dll
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->date('tanggal')->default(now());
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_malls');
    }
};
