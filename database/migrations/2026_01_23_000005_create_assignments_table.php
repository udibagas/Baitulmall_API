<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->onDelete('restrict');
            $table->foreignId('structure_id')->constrained('organization_structures')->onDelete('restrict');
            $table->string('jabatan'); // Ketua, Sekretaris, Amil
            $table->string('tipe_sk')->default('SK Resmi');
            $table->string('no_sk')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['Aktif', 'Cuti', 'Purna'])->default('Aktif');
            $table->json('kewenangan')->nullable(); // { "can_sign_zakat": true }
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Prevent duplicates
            $table->unique(['person_id', 'structure_id', 'jabatan', 'tanggal_mulai'], 'assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
