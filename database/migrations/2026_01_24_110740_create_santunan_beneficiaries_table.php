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
        Schema::create('santunan_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('alamat')->nullable();
            $table->enum('jenis', ['yatim', 'dhuafa'])->default('yatim');
            $table->foreignId('rt_id')->constrained('rts')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->text('keterangan')->nullable();
            $table->json('data_tambahan')->nullable(); // Stores wali, umur, rekening etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('santunan_beneficiaries');
    }
};
