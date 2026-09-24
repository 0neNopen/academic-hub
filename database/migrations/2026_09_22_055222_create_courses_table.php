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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Contoh: Pemrograman Web Lanjut
            $table->string('code')->nullable(); // Contoh: IF-302
            $table->string('lecturer_name')->nullable();
            $table->string('day_of_week')->nullable(); // Contoh: Senin
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('color', 20)->default('#3b82f6'); // Warna kartu matkul di UI React
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};