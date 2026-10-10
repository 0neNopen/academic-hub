<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_d3_ti_registration_creates_package_courses(): void
    {
        $response = $this->post('/register', [
            'name' => 'Mahasiswa D3 TI',
            'email' => 'ti.student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_d3_ti' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = \App\Models\User::where('email', 'ti.student@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(12, $user->courses()->count());

        // Pastikan ruangan dan dosen pengampu benar
        $iot = $user->courses()->where('name', 'Internet of Things')->first();
        $this->assertNotNull($iot);
        $this->assertEquals('Lab Jaringan', $iot->room);
        $this->assertEquals('Nanang Maulana Yoeseph, S.Si., M.Cs.', $iot->lecturer_name);
        $this->assertEquals('Kamis', $iot->day_of_week);

        $kewirausahaan = $user->courses()->where('name', 'Kewirausahaan')->first();
        $this->assertNotNull($kewirausahaan);
        $this->assertEquals('Lab Pemrograman', $kewirausahaan->room);
        $this->assertEquals('Dr. Fajar Danur Isnantyo S.T., M.Sc.', $kewirausahaan->lecturer_name);
        $this->assertEquals('Rabu', $kewirausahaan->day_of_week);

        $tefa = $user->courses()->where('name', 'Manajemen Proyek Teknologi Informasi')->first();
        $this->assertNotNull($tefa);
        $this->assertEquals('TEFA', $tefa->room);
        $this->assertEquals('Dr. Ovide Decroly Wisnu Ardhi, S.T., M.Eng.', $tefa->lecturer_name);
    }
}
