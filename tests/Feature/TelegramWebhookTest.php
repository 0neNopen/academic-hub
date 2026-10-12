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
        $mock->shouldReceive('removeKeyboardMarkup')->byDefault()->andReturn(['remove_keyboard' => true]);
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
            'description' => 'Kerjakan modul 1 sampai 3 format PDF',
            'submission_url' => 'https://classroom.google.com/test',
            'status' => 'pending',
            'deadline' => now()->addDays(2),
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 
                    && str_contains($msg, 'Tugas Besar Basis Data')
                    && str_contains($msg, 'Kerjakan modul 1 sampai 3')
                    && str_contains($msg, 'https://classroom.google.com/test');
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

    public function test_telegram_webhook_handles_materi_command(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
        ]);

        $course = Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Struktur Data & Algoritma',
        ]);

        \App\Models\Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Pertemuan 1 - Pengantar Array',
            'meeting_number' => 1,
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678 
                    && str_contains($msg, 'Arsip Berkas Materi Perkuliahan')
                    && str_contains($msg, 'Struktur Data & Algoritma')
                    && str_contains($msg, '1 berkas');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/materi',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_reset_command_for_linked_user(): void
    {
        $user = User::factory()->create([
            'email' => 'novv.a2n@gmail.com',
            'telegram_chat_id' => '12345678',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessageWithId')
            ->once()
            ->withArgs(function ($chatId, $msg, $keyboard) {
                return $chatId == 12345678
                    && str_contains($msg, 'Tautan Reset Kata Sandi Anda')
                    && !empty($keyboard);
            })
            ->andReturn(8888);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/reset',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_portal_command_for_linked_user(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
            'campus_name' => 'Politeknik Negeri Malang',
            'campus_portals' => [
                ['name' => 'OCW', 'url' => 'https://ocw.polinema.ac.id'],
                ['name' => 'SPADA', 'url' => 'https://spada.polinema.ac.id'],
            ],
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg, $buttons) {
                return $chatId == 12345678
                    && str_contains($msg, 'Akses Cepat Portal Kampus')
                    && str_contains($msg, 'OCW')
                    && !empty($buttons);
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/portal',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_help_command_for_linked_user_with_custom_preferences(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
            'schedule_reminder_time' => '06:30',
            'task_reminder_early' => '24',
            'task_reminder_late' => '5',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678
                    && str_contains($msg, 'Panduan Perintah Academic Hub Bot')
                    && str_contains($msg, 'deadline tugas (H-24 & H-5)')
                    && str_contains($msg, 'pukul 06:30 WIB');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/help',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_help_command_when_reminders_disabled(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
            'schedule_reminder_time' => '07:00',
            'task_reminder_early' => '0',
            'task_reminder_late' => '0',
        ]);

        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 12345678
                    && str_contains($msg, 'deadline tugas (pengingat dinonaktifkan)')
                    && str_contains($msg, 'pukul 07:00 WIB');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345678],
                'text' => '/help',
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_handles_help_command_for_unlinked_user(): void
    {
        $telegramMock = $this->makeMock();
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $msg) {
                return $chatId == 998877
                    && str_contains($msg, 'Panduan Perintah Academic Hub Bot')
                    && str_contains($msg, 'deadline tugas (H-24 & H-3)')
                    && str_contains($msg, 'pukul 06:00 WIB (dapat disesuaikan di profil web)');
            })
            ->andReturn(true);

        $this->app->instance(TelegramService::class, $telegramMock);

        $response = $this->postJson('/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 998877],
                'text' => '/help',
            ],
        ]);

        $response->assertStatus(200);
    }
}

