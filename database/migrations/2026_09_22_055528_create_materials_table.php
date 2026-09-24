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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // Contoh: "Slide Pertemuan 1 - Arsitektur MVC"
            $table->unsignedSmallInteger('meeting_number')->nullable(); // Pertemuan ke- (1, 2, dst)
            $table->string('file_path')->nullable(); // Lokasi penyimpanan lokal
            $table->text('external_link')->nullable(); // Link Google Drive / LMS cadangan
            $table->text('notes')->nullable(); // Catatan singkat materi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};