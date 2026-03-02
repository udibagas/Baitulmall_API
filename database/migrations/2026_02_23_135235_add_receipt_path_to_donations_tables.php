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
            $table->string('receipt_path')->nullable()->after('status_bayar');
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('keterangan');
        });

        Schema::table('sedekah', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('muzaki', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });

        Schema::table('zakat_malls', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });

        Schema::table('sedekah', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }
};
