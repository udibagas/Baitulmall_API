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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('seller_name');
            $table->string('seller_phone');
            $table->enum('category', ['Kuliner', 'Kerajinan', 'Jasa', 'Lainnya'])->default('Lainnya');
            $table->string('image_url')->nullable();
            
            // Context location
            $table->unsignedBigInteger('rt_id')->nullable();
            $table->foreign('rt_id')->references('id')->on('rts')->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
