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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->text('submission_url')->nullable(); // Link Google Form / LMS / Drive pengumpulan
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->string('submission_file_path')->nullable(); // Berkas hasil kerja sendiri (opsional)
            $table->timestamp('reminder_h24_sent_at')->nullable(); // Log notifikasi H-1 (24 jam)
            $table->timestamp('reminder_h3_sent_at')->nullable();  // Log notifikasi darurat H-3 jam
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};