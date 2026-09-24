<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_assignment_for_own_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/courses/{$course->id}/assignments", [
            'title' => 'Tugas 1 Basis Data',
            'description' => 'Kerjakan ERD',
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'submission_url' => 'https://forms.gle/test',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'title' => 'Tugas 1 Basis Data',
        ]);
    }

    public function test_user_cannot_create_assignment_for_another_users_course(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->post("/courses/{$course->id}/assignments", [
            'title' => 'Hacked Task',
            'deadline' => now()->addDays(1)->format('Y-m-d H:i:s'),
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_update_their_assignment(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Judul Lama',
        ]);

        $response = $this->actingAs($user)->put("/assignments/{$assignment->id}", [
            'title' => 'Judul Baru Diperbarui',
            'description' => 'Deskripsi Baru',
            'deadline' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'submission_url' => 'https://classroom.google.com/test',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Judul Baru Diperbarui',
        ]);
    }

    public function test_updating_deadline_resets_reminder_sent_timestamps(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'deadline' => Carbon::now()->addHours(2),
            'reminder_h24_sent_at' => Carbon::now()->subDay(),
            'reminder_h3_sent_at' => Carbon::now()->subHour(),
        ]);

        $newDeadline = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($user)->put("/assignments/{$assignment->id}", [
            'title' => $assignment->title,
            'deadline' => $newDeadline,
        ]);

        $response->assertSessionHasNoErrors();
        $assignment->refresh();

        $this->assertNull($assignment->reminder_h24_sent_at, 'reminder_h24_sent_at harus direset saat deadline berubah.');
        $this->assertNull($assignment->reminder_h3_sent_at, 'reminder_h3_sent_at harus direset saat deadline berubah.');
    }

    public function test_user_cannot_update_another_users_assignment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user2)->put("/assignments/{$assignment->id}", [
            'title' => 'Malicious Edit',
            'deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_update_assignment_status(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->patch("/assignments/{$assignment->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_their_assignment(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->delete("/assignments/{$assignment->id}");
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_assignment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user2)->delete("/assignments/{$assignment->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
        ]);
    }
}
