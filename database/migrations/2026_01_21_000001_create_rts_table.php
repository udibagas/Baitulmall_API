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
        Schema::create('rts', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 2)->unique()->comment('RT code: 01-07');
            $table->string('rw', 2)->default('01')->comment('RW code');
            $table->string('ketua')->nullable()->comment('RT head name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            
            $table->index('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rts');
    }
};
