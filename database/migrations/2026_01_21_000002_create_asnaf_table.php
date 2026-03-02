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
        Schema::create('asnaf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts')->onDelete('cascade');
            $table->string('nama');
            $table->enum('kategori', ['Fakir', 'Miskin', 'Fisabilillah', 'Amil']);
            $table->integer('jumlah_jiwa')->unsigned();
            $table->year('tahun');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['rt_id', 'kategori', 'tahun']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asnaf');
    }
};
