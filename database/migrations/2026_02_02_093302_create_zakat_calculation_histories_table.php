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
        Schema::create('zakat_calculation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('muzaki_id')->constrained('muzaki')->onDelete('cascade');
            $table->string('zakat_type'); // Maal, Profesi, Perdagangan
            $table->decimal('total_assets', 20, 2);
            $table->decimal('deductible_debt', 20, 2)->default(0);
            $table->decimal('nisab_threshold', 20, 2);
            $table->decimal('zakat_rates_percent', 5, 2)->default(2.5);
            $table->decimal('calculated_amount', 20, 2);
            $table->boolean('is_payable')->default(false);
            $table->boolean('haul_met')->default(true);
            $table->date('calculation_date');
            $table->json('details')->nullable(); // Stores inputs like modal, keuntungan, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_calculation_histories');
    }
};
