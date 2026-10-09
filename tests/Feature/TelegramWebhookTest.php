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

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function makeMock(): Mockery\MockInterface
    {
        $mock = Mockery::mock(TelegramService::class);
        $mock->shouldReceive('buildMainKeyboard')->byDefault()->andReturn([]);
        return $mock;
    }

    public function test_telegram_webhook_responds_to_start_for_linked_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'telegram_chat_id' => '12345678',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 && str_contains($msg, 'Budi Santoso') && str_contains($msg, 'Akun Telegram Anda Berhasil Terhubung');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 12345678],
                'from' => ['first_name' => 'Budi'],
                'text' => '/start',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_telegram_webhook_responds_to_start_for_unlinked_user(): void
    {
        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg, $buttons) {
                return $chatId == 998877 && str_contains($msg, '998877') && str_contains($msg, 'Cara menghubungkan');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'message_id' => 2,
                'chat' => ['id' => 998877],
                'from' => ['first_name' => 'Siti'],
                'text' => '/start',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_telegram_webhook_handles_jadwal_command(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
        ]);

        $today = Carbon::now('Asia/Jakarta')->locale('id')->isoFormat('dddd');

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Algoritma & Pemrograman',
            'day_of_week' => $today,
            'start_time' => '08:00',
            'end_time' => '10:30',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 && str_contains($msg, 'Algoritma & Pemrograman');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/jadwal',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_tugas_command(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
        ]);

        $course = Course::factory()->create(['user_id' => $user->id]);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Besar Basis Data',
            'status' => 'pending',
            'deadline' => now()->addDays(2),
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 && str_contains($msg, 'Tugas Besar Basis Data');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/tugas',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_semua_jadwal_command(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Kecerdasan Buatan',
            'day_of_week' => 'Rabu',
            'start_time' => '13:00',
            'end_time' => '15:30',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 && str_contains($msg, 'Pemrograman Web') && str_contains($msg, 'Kecerdasan Buatan');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/semua_jadwal',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_button_text_trigger(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 && str_contains($msg, 'Jadwal Kuliah Hari Ini');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '📅 Jadwal Hari Ini',
            ],
        ]);

        $response->assertStatus(200);
    }
}
