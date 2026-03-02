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
        Schema::create('crowdfunding_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('crowdfunding_campaigns')->onDelete('cascade');
            $table->string('donor_name')->default('Hamba Allah');
            $table->string('donor_phone')->nullable();
            $table->string('donor_email')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable(); // e.g., 'transfer', 'cash', 'qris'
            $table->string('status')->default('pending'); // pending, paid, verified
            $table->string('proof_url')->nullable(); // URL to payment proof image
            $table->text('notes')->nullable(); // Doa atau pesan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crowdfunding_donations');
    }
};
