<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Optional link to login
            $table->string('nik')->unique()->nullable();
            $table->string('nama_lengkap');
            $table->string('panggilan')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->text('alamat_ktp')->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->string('no_wa')->index()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('foto_url')->nullable();
            $table->enum('status_hidup', ['Hidup', 'Meninggal'])->default('Hidup');
            $table->timestamps();
            $table->softDeletes(); // Never delete master data
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
