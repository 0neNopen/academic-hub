<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_material_to_their_course(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('slide_kuliah.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post("/courses/{$course->id}/materials", [
            'title' => 'Pertemuan 1 - Arsitektur Web',
            'meeting_number' => 1,
            'file' => $file,
            'notes' => 'Materi pengantar MVC',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'title' => 'Pertemuan 1 - Arsitektur Web',
            'meeting_number' => 1,
        ]);

        $material = Material::where('course_id', $course->id)->first();
        $this->assertNotNull($material->file_path);
        Storage::disk('public')->assertExists($material->file_path);
    }

    public function test_user_cannot_upload_material_to_another_users_course(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->post("/courses/{$course->id}/materials", [
            'title' => 'Unauthorized Material',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_download_their_course_material(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $filePath = 'materials/sample_doc.pdf';
        Storage::disk('public')->put($filePath, 'Fake PDF Content');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Modul Praktikum',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)->get("/materials/{$material->id}/download");

        $response->assertOk();
        $response->assertDownload('Modul_Praktikum.pdf');
    }

    public function test_user_cannot_download_another_users_material(): void
    {
        Storage::fake('public');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $filePath = 'materials/confidential.pdf';
        Storage::disk('public')->put($filePath, 'Secret Content');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Confidential Material',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user2)->get("/materials/{$material->id}/download");

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_material(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $filePath = 'materials/to_delete.pdf';
        Storage::disk('public')->put($filePath, 'Delete Me');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)->delete("/materials/{$material->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_user_cannot_delete_another_users_material(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);
        $material = Material::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user2)->delete("/materials/{$material->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_user_can_preview_their_course_material(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $filePath = 'materials/slide_preview.pdf';
        Storage::disk('public')->put($filePath, 'PDF Preview Content');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Slide Kuliah',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user)->get("/materials/{$material->id}/preview");

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_user_cannot_preview_another_users_material(): void
    {
        Storage::fake('public');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);

        $filePath = 'materials/private_slide.pdf';
        Storage::disk('public')->put($filePath, 'Private PDF Content');

        $material = Material::factory()->create([
            'course_id' => $course->id,
            'title' => 'Private Slide',
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($user2)->get("/materials/{$material->id}/preview");

        $response->assertForbidden();
    }

    public function test_user_can_upload_image_as_material(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $imageFile = UploadedFile::fake()->image('diagram_arsitektur.png', 800, 600);

        $response = $this->actingAs($user)->post("/courses/{$course->id}/materials", [
            'title' => 'Diagram Arsitektur Sistem',
            'meeting_number' => 2,
            'file' => $imageFile,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materials', [
            'course_id' => $course->id,
            'title' => 'Diagram Arsitektur Sistem',
            'meeting_number' => 2,
        ]);

        $material = Material::where('title', 'Diagram Arsitektur Sistem')->first();
        $this->assertNotNull($material->file_path);
        Storage::disk('public')->assertExists($material->file_path);
    }

    public function test_user_can_update_their_material(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $initialFile = UploadedFile::fake()->create('old.pdf', 100);

        $material = Material::create([
            'course_id' => $course->id,
            'title' => 'Materi Lama',
            'meeting_number' => 1,
            'file_path' => $initialFile->store('materials', 'public'),
        ]);

        $newFile = UploadedFile::fake()->image('new_diagram.jpg', 600, 400);

        $response = $this->actingAs($user)->post("/materials/{$material->id}", [
            'title' => 'Materi Baru Diperbarui',
            'meeting_number' => 3,
            'external_link' => 'https://example.com/updated',
            'notes' => 'Catatan revisi',
            'file' => $newFile,
        ]);

        $response->assertSessionHasNoErrors();
        $material->refresh();

        $this->assertEquals('Materi Baru Diperbarui', $material->title);
        $this->assertEquals(3, $material->meeting_number);
        $this->assertEquals('https://example.com/updated', $material->external_link);
        $this->assertEquals('Catatan revisi', $material->notes);
        Storage::disk('public')->assertExists($material->file_path);
    }

    public function test_user_cannot_update_another_users_material(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user1->id]);
        $material = Material::factory()->create(['course_id' => $course->id, 'title' => 'Original']);

        $response = $this->actingAs($user2)->post("/materials/{$material->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertForbidden();
        $this->assertEquals('Original', $material->fresh()->title);
    }

    public function test_meeting_number_cannot_be_negative(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/courses/{$course->id}/materials", [
            'title' => 'Test Pertemuan Minus',
            'meeting_number' => -1,
        ]);

        $response->assertSessionHasErrors(['meeting_number']);
    }

    public function test_svg_upload_is_rejected_for_security(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $svgFile = UploadedFile::fake()->create('malicious.svg', 100, 'image/svg+xml');

        $response = $this->actingAs($user)->post("/courses/{$course->id}/materials", [
            'title' => 'Test SVG Security',
            'file' => $svgFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_deleting_course_cleans_up_material_files_from_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('slide.pdf', 200, 'application/pdf');

        $this->actingAs($user)->post("/courses/{$course->id}/materials", [
            'title' => 'Slide Penting',
            'file' => $file,
        ]);

        $material = Material::where('course_id', $course->id)->first();
        $filePath = $material->file_path;
        Storage::disk('public')->assertExists($filePath);

        // Hapus course
        $deleteResponse = $this->actingAs($user)->delete("/courses/{$course->id}");
        $deleteResponse->assertRedirect(route('dashboard'));

        // Pastikan record di database terhapus dan berkas fisik ikut terhapus dari storage
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        Storage::disk('public')->assertMissing($filePath);
    }
}
