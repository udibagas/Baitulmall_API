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
        Schema::table('asnaf', function (Blueprint $table) {
            $table->decimal('pendapatan', 15, 2)->nullable()->after('kategori');
            $table->string('kondisi_rumah')->nullable()->after('pendapatan'); // milik_sendiri_permanen, milik_sendiri_semi, sewa, numpang
            $table->integer('score')->nullable()->after('kondisi_rumah');
            $table->json('scoring_details')->nullable()->after('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asnaf', function (Blueprint $table) {
            //
        });
    }
};
