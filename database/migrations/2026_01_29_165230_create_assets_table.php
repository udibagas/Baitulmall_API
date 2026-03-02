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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., BRG-001
            $table->string('category'); // Elektronik, Furniture, Kendaraan
            $table->enum('condition', ['good', 'damaged', 'lost']);
            $table->date('acquisition_date')->nullable();
            $table->decimal('value', 15, 2)->default(0);
            $table->boolean('is_lendable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
