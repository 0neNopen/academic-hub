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

        $this->artisan('courses:send-daily-schedule', ['--day' => 'Senin'])
            ->assertExitCode(0);
    }

    public function test_daily_schedule_sends_saturday_weekend_message_when_no_classes(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'telegram_chat_id' => '123123123',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Fisika Dasar',
            'day_of_week' => 'Senin',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('123123123', Mockery::on(function ($message) {
                return str_contains($message, 'HAPPY WEEKEND! (SABTU SANTAI)')
                    && str_contains($message, 'Budi Santoso')
                    && str_contains($message, 'rehat sejenak')
                    && str_contains($message, '/tugas');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', [
            '--user' => $user->id,
            '--day' => 'Sabtu',
        ])->assertExitCode(0);
    }

    public function test_daily_schedule_sends_sunday_weekend_message_with_monday_preview(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'telegram_chat_id' => '456456456',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kalkulus Lanjut',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'room' => 'Ruang 301',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('456456456', Mockery::on(function ($message) {
                return str_contains($message, 'SELAMAT HARI MINGGU!')
                    && str_contains($message, 'Siti Nurhaliza')
                    && str_contains($message, 'Intipan Kuliah Besok (Senin)')
                    && str_contains($message, 'Kalkulus Lanjut')
                    && str_contains($message, 'Ruang 301');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', [
            '--user' => $user->id,
            '--day' => 'Minggu',
        ])->assertExitCode(0);
    }

    public function test_daily_schedule_notifies_user_with_no_courses_registered(): void
    {
        $user = User::factory()->create([
            'name' => 'Mahasiswa Baru',
            'telegram_chat_id' => '789789789',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('789789789', Mockery::on(function ($message) {
                return str_contains($message, 'Jadwal Kuliah Belum Terdaftar')
                    && str_contains($message, 'Mahasiswa Baru')
                    && str_contains($message, 'Yuk lengkapi jadwal kuliah');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('courses:send-daily-schedule', [
            '--user' => $user->id,
            '--day' => 'Senin',
        ])->assertExitCode(0);
    }
}
