<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_update_telegram_chat_id(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'telegram_chat_id' => '123456789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('123456789', $user->fresh()->telegram_chat_id);
    }

    public function test_user_cannot_use_telegram_chat_id_already_taken_by_another_user(): void
    {
        $existingUser = User::factory()->create([
            'telegram_chat_id' => '999888777',
        ]);

        $user = User::factory()->create([
            'telegram_chat_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'telegram_chat_id' => '999888777',
            ]);

        $response
            ->assertSessionHasErrors(['telegram_chat_id' => 'Telegram Chat ID ini sudah terhubung ke akun lain. Mohon periksa kembali Chat ID Anda.'])
            ->assertRedirect('/profile');

        $this->assertNull($user->fresh()->telegram_chat_id);
    }

    public function test_user_can_keep_their_own_telegram_chat_id_when_updating_profile(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '999888777',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Updated Name',
                'email' => $user->email,
                'telegram_chat_id' => '999888777',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertSame('999888777', $user->fresh()->telegram_chat_id);
    }

    public function test_user_can_update_notification_and_campus_settings(): void
    {
        $user = User::factory()->create([
            'schedule_reminder_time' => '06:00',
            'task_reminder_early' => '24',
            'task_reminder_late' => '3',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'schedule_reminder_time' => '07:00',
                'task_reminder_early' => '48',
                'task_reminder_late' => '5',
                'campus_name' => 'Universitas Sebelas Maret',
                'campus_portal_url' => 'https://ocw.uns.ac.id',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('07:00', $user->schedule_reminder_time);
        $this->assertSame('48', $user->task_reminder_early);
        $this->assertSame('5', $user->task_reminder_late);
        $this->assertSame('Universitas Sebelas Maret', $user->campus_name);
        $this->assertSame('https://ocw.uns.ac.id', $user->campus_portal_url);
    }

    public function test_user_can_update_multiple_campus_portals(): void
    {
        $user = User::factory()->create();

        $portals = [
            ['name' => 'SIAKAD Utama', 'url' => 'https://siakad.kampus.ac.id'],
            ['name' => 'LMS Moodle', 'url' => 'https://lms.kampus.ac.id'],
            ['name' => 'Presensi Online', 'url' => 'https://presensi.kampus.ac.id'],
        ];

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'campus_name' => 'Politeknik Negeri',
                'campus_portals' => $portals,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Politeknik Negeri', $user->campus_name);
        $this->assertCount(3, $user->campus_portals);
        $this->assertSame('SIAKAD Utama', $user->campus_portals[0]['name']);
        $this->assertSame('https://siakad.kampus.ac.id', $user->campus_portals[0]['url']);
        $this->assertCount(3, $user->all_campus_portals);
    }

    public function test_empty_campus_portals_are_filtered_out(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'campus_portals' => [
                    ['name' => '', 'url' => ''],
                    ['name' => '   ', 'url' => ''],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertEmpty($user->campus_portals);
    }

    public function test_user_can_update_telegram_chat_id_when_campus_portals_contains_empty_url_rows(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'telegram_chat_id' => '7107606089',
                'campus_portals' => [
                    ['name' => 'SIAKAD', 'url' => ''],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('7107606089', $user->telegram_chat_id);
        $this->assertEmpty($user->campus_portals);
    }

    public function test_portals_with_name_only_and_blank_url_are_dropped_without_validation_errors(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'campus_portals' => [
                    ['name' => 'SIAKAD', 'url' => ''],
                    ['name' => 'LMS Moodle', 'url' => 'https://lms.kampus.ac.id'],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertCount(1, $user->campus_portals);
        $this->assertSame('LMS Moodle', $user->campus_portals[0]['name']);
        $this->assertSame('https://lms.kampus.ac.id', $user->campus_portals[0]['url']);
    }

    public function test_deleting_user_account_cleans_up_course_material_files_from_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $filePath = 'materials/user_deletion_test.pdf';
        Storage::disk('public')->put($filePath, 'User material content');

        $material = Material::create([
            'course_id' => $course->id,
            'title' => 'Materi User Hapus',
            'file_path' => $filePath,
        ]);

        Storage::disk('public')->assertExists($filePath);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('public')->assertMissing($filePath);
    }
}
