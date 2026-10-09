<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DailyScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_schedule_sends_to_user_with_classes_today(): void
    {
        Carbon::setLocale('id');
        $user = User::factory()->create([
            'name' => 'Ahmad Dahlan',
            'telegram_chat_id' => '1122334455',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Sistem Terdistribusi',
            'code' => 'IF-401',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:30',
            'lecturer_name' => 'Dr. Bambang',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('1122334455', Mockery::on(function ($message) {
                return str_contains($message, 'JADWAL KULIAH HARI INI')
                    && str_contains($message, 'Ahmad Dahlan')
                    && str_contains($message, 'Sistem Terdistribusi')
                    && str_contains($message, 'IF-401')
                    && str_contains($message, 'Dr. Bambang');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', ['--day' => 'Senin'])
            ->assertExitCode(0);
    }

    public function test_daily_schedule_handles_user_with_no_classes_today(): void
    {
        $user = User::factory()->create([
            'name' => 'Dewi Sartika',
            'telegram_chat_id' => '9988776655',
        ]);

        // Matkul di hari Selasa, tapi dicek hari Senin
        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Pemrograman Web Lanjut',
            'day_of_week' => 'Selasa',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('9988776655', Mockery::on(function ($message) {
                return str_contains($message, 'JADWAL KULIAH HARI INI')
                    && str_contains($message, 'Dewi Sartika')
                    && str_contains($message, 'Tidak ada jadwal kuliah hari ini');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', [
            '--user' => $user->id,
            '--day' => 'Senin',
        ])->assertExitCode(0);
    }

    public function test_daily_schedule_includes_assignments_due_today(): void
    {
        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($tz);

        $indonesianDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $todayName = $indonesianDays[$now->dayOfWeek];

        $user = User::factory()->create([
            'name' => 'Farhan Pratama',
            'telegram_chat_id' => '5566778899',
        ]);

        $course = Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Basis Data Lanjut',
            'day_of_week' => $todayName,
        ]);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Normalisasi Tabel 3NF',
            'status' => 'pending',
            'deadline' => $now->copy()->setHour(23)->setMinute(59),
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('5566778899', Mockery::on(function ($message) {
                return str_contains($message, 'JADWAL KULIAH HARI INI')
                    && str_contains($message, 'Basis Data Lanjut')
                    && str_contains($message, 'Tugas Normalisasi Tabel 3NF');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', ['--user' => $user->id])
            ->assertExitCode(0);
    }

    public function test_daily_schedule_skips_users_without_telegram_chat_id(): void
    {
        $user = User::factory()->create([
            'name' => 'User Tanpa Telegram',
            'telegram_chat_id' => null,
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Aljabar Linier',
            'day_of_week' => 'Senin',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldNotReceive('sendMessage');

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', ['--day' => 'Senin'])
            ->assertExitCode(0);
    }
}
