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
        Schema::table('organization_structures', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('nama_struktur');
            $table->string('lokasi')->nullable()->after('deskripsi');
            $table->string('status')->default('Draft')->after('lokasi');
            $table->longText('rundown')->nullable()->after('status');
            $table->longText('anggaran')->nullable()->after('rundown');
            $table->longText('pemasukan')->nullable()->after('anggaran');
            $table->longText('checklist')->nullable()->after('pemasukan');
        });
    }

    public function down(): void
    {
        Schema::table('organization_structures', function (Blueprint $table) {
            $table->dropColumn(['deskripsi', 'lokasi', 'status', 'rundown', 'anggaran', 'pemasukan', 'checklist']);
        });
    }
};
