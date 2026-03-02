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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone', 20);
            $table->text('message');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('provider', 50)->default('fonnte');
            $table->text('response_data')->nullable(); // Store JSON response
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
