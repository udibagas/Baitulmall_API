<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organization_structures')->onDelete('set null');
            $table->string('kode_struktur')->unique(); // e.g. BAITULMALL_2024
            $table->string('nama_struktur'); // e.g. Pengurus Baitulmall 2024-2029
            $table->enum('tipe', ['Struktural', 'Kepanitiaan', 'Project', 'Event', 'Panitia', 'Agenda'])->default('Struktural');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_structures');
    }
};
