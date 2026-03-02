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
        Schema::table('santunan', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('id')->constrained('santunan_activities')->onDelete('set null');
            $table->enum('kategori', ['yatim', 'dhuafa', 'kematian'])->default('yatim')->after('besaran')->comment('Beneficiary category');
        });

        Schema::table('santunan_donations', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('id')->constrained('santunan_activities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('santunan_tables', function (Blueprint $table) {
            //
        });
    }
};
