<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_course(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/courses', [
            'name' => 'Pemrograman Web',
            'code' => 'IF-101',
            'lecturer_name' => 'Dr. Budi',
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:30',
            'color' => '#3b82f6',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courses', [
            'user_id' => $user->id,
            'name' => 'Pemrograman Web',
            'code' => 'IF-101',
        ]);
    }

    public function test_user_can_view_their_own_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Pertemuan 1 - Pengenalan',
        ]);

        $response = $this->actingAs($user)->get("/courses/{$course->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Courses/Show')
            ->has('course.materials', 1)
            ->where('course.materials.0.title', 'Pertemuan 1 - Pengenalan')
            ->has('course.materials.0.created_at')
        );
    }

    public function test_user_cannot_view_another_users_course(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get("/courses/{$course->id}");
        $response->assertForbidden();
    }

    public function test_user_can_update_their_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $user->id,
            'name' => 'Nama Lama',
        ]);

        $response = $this->actingAs($user)->put("/courses/{$course->id}", [
            'name' => 'Nama Baru',
            'code' => 'IF-202',
            'lecturer_name' => 'Prof. Andi',
            'day_of_week' => 'Rabu',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Nama Baru',
            'code' => 'IF-202',
        ]);
    }

    public function test_user_cannot_update_another_users_course(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create([
            'user_id' => $user1->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($user2)->put("/courses/{$course->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_user_can_delete_their_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/courses/{$course->id}");
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('courses', [
            'id' => $course->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_course(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete("/courses/{$course->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
        ]);
    }
}
