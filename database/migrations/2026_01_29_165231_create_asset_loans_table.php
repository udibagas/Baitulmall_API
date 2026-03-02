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
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('borrower_name');
            $table->string('borrower_phone', 20);
            $table->dateTime('loan_date');
            $table->dateTime('expected_return_date');
            $table->dateTime('actual_return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
    }
};
