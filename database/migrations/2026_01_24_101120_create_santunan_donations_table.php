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
        Schema::create('santunan_donations', function (Blueprint $table) {
            $table->id();
            $table->string('nama_donatur');
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
            $table->integer('tahun');
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Optional: who input it
            $table->timestamps();

            $table->index(['tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('santunan_donations');
    }
};
