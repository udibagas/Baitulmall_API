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
        Schema::table('muzaki', function (Blueprint $table) {
            $table->string('no_hp')->nullable()->after('nama');
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->string('no_hp')->nullable()->after('nama_muzaki');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muzaki', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });
    }
};
