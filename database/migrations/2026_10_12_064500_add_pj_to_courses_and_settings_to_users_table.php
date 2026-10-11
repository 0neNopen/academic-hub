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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('pj_name', 100)->nullable()->after('room');
            $table->string('pj_phone', 25)->nullable()->after('pj_name');
            $table->string('attendance_url', 500)->nullable()->after('pj_phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('schedule_reminder_time', 5)->default('06:00')->after('notification_channel');
            $table->string('task_reminder_early', 10)->default('24')->after('schedule_reminder_time');
            $table->string('task_reminder_late', 10)->default('3')->after('task_reminder_early');
            $table->string('campus_name', 100)->nullable()->after('task_reminder_late');
            $table->string('campus_portal_url', 500)->nullable()->after('campus_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['pj_name', 'pj_phone', 'attendance_url']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_reminder_time',
                'task_reminder_early',
                'task_reminder_late',
                'campus_name',
                'campus_portal_url',
            ]);
        });
    }
};
