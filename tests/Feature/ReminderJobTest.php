<?php

namespace Tests\Feature;

use App\Jobs\SendAssignmentReminderJob;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ReminderJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_dispatches_reminder_job_for_upcoming_deadline(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'telegram_chat_id' => '12345678',
            'notification_channel' => 'telegram',
        ]);
        $course = Course::factory()->create(['user_id' => $user->id]);

        // Tugas dengan deadline 12 jam lagi (kategori H-24)
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'status' => 'pending',
            'deadline' => Carbon::now()->addHours(12),
            'reminder_h24_sent_at' => null,
            'reminder_h3_sent_at' => null,
        ]);

        $this->artisan('assignments:send-reminders')
            ->assertSuccessful();

        Queue::assertPushed(SendAssignmentReminderJob::class, function ($job) use ($assignment) {
            return $job->assignmentId === $assignment->id && $job->isH3 === false;
        });
    }

    public function test_scheduler_does_not_dispatch_job_for_far_future_deadline(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        // Tugas dengan deadline 5 hari lagi (belum waktunya diingatkan)
        Assignment::factory()->create([
            'course_id' => $course->id,
            'status' => 'pending',
            'deadline' => Carbon::now()->addDays(5),
            'reminder_h24_sent_at' => null,
            'reminder_h3_sent_at' => null,
        ]);

        $this->artisan('assignments:send-reminders')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_reminder_job_sends_notification_and_updates_timestamp(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '99887766',
            'notification_channel' => 'telegram',
        ]);
        $course = Course::factory()->create(['user_id' => $user->id]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'status' => 'pending',
            'deadline' => Carbon::now()->addHours(2), // H-3 Jam
            'reminder_h24_sent_at' => null,
            'reminder_h3_sent_at' => null,
        ]);

        $telegramMock = Mockery::mock(TelegramService::class);
        $telegramMock->shouldReceive('buildSubmissionButton')->andReturn(null);
        $telegramMock->shouldReceive('sendMessage')
            ->once()
            ->with('99887766', Mockery::type('string'), Mockery::any())
            ->andReturn(true);

        $waMock = Mockery::mock(WhatsAppService::class);
        $waMock->shouldReceive('isConfigured')->andReturn(false);

        $job = new SendAssignmentReminderJob($assignment->id, true);
        $job->handle($waMock, $telegramMock);

        $assignment->refresh();
        $this->assertNotNull($assignment->reminder_h3_sent_at);
    }
}
