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
            $table->longText('panitia')->nullable()->after('checklist');
        });
    }

    public function down(): void
    {
        Schema::table('organization_structures', function (Blueprint $table) {
            $table->dropColumn('panitia');
        });
    }
};
