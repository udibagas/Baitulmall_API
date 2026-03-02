<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('organization_structures')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->dateTime('schedule_date');
            $table->string('location')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_posts');
    }
};
