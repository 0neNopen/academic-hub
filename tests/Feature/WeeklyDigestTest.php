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

class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_digest_sends_to_user_with_telegram_chat_id(): void
    {
        Carbon::setLocale('id');
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'telegram_chat_id' => '123456789',
        ]);

        $course = Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kecerdasan Buatan',
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Implementasi Algoritma A*',
            'status' => 'pending',
            'deadline' => Carbon::now()->startOfWeek()->addDays(2)->setHour(23)->setMinute(59),
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('123456789', Mockery::on(function ($message) {
                return str_contains($message, 'RINGKASAN AKADEMIK MINGGU INI')
                    && str_contains($message, 'Budi Santoso')
                    && str_contains($message, 'Implementasi Algoritma A*');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('assignments:send-weekly-digest')
            ->assertExitCode(0);
    }

    public function test_weekly_digest_handles_user_with_no_assignments_this_week(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Aminah',
            'telegram_chat_id' => '987654321',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Pemrograman Web',
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('987654321', Mockery::on(function ($message) {
                return str_contains($message, 'RINGKASAN AKADEMIK MINGGU INI')
                    && str_contains($message, 'Tidak ada deadline tugas minggu ini');
            }))
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('assignments:send-weekly-digest', ['--user' => $user->id])
            ->assertExitCode(0);
    }

    public function test_weekly_digest_skips_users_without_telegram_chat_id(): void
    {
        User::factory()->create([
            'name' => 'User Tanpa Telegram',
            'telegram_chat_id' => null,
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldNotReceive('sendMessage');

        $this->app->instance(TelegramService::class, $telegramMock);

        $this->artisan('assignments:send-weekly-digest')
            ->assertExitCode(0);
    }
}
