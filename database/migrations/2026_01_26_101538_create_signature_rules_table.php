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
        Schema::create('signature_rules', function (Blueprint $table) {
            $table->id();
            $table->string('page_name')->index(); // 'zakat_fitrah', 'zakat_mall', 'sedekah', ...
            $table->string('category_filter')->default('ALL'); // 'Fakir', 'Miskin' or 'ALL'
            $table->string('rt_filter')->default('ALL'); // '01', '02', 'ALL'
            
            $table->foreignId('left_signer_id')->nullable()->constrained('signers')->nullOnDelete();
            $table->foreignId('right_signer_id')->nullable()->constrained('signers')->nullOnDelete();
            
            $table->integer('priority')->default(0); // For ordering rules
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_rules');
    }
};
