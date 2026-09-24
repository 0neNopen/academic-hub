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
        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['status', 'deadline'], 'assignments_status_deadline_idx');
            $table->index(['course_id', 'status', 'deadline'], 'assignments_course_status_deadline_idx');
            $table->index(['reminder_h24_sent_at', 'reminder_h3_sent_at'], 'assignments_reminders_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index(['user_id', 'day_of_week'], 'courses_user_day_idx');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->index(['course_id', 'meeting_number'], 'materials_course_meeting_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('assignments_status_deadline_idx');
            $table->dropIndex('assignments_course_status_deadline_idx');
            $table->dropIndex('assignments_reminders_idx');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_user_day_idx');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex('materials_course_meeting_idx');
        });
    }
};
