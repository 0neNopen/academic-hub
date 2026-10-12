<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_warns_when_not_configured(): void
    {
        config(['services.google.client_id' => '']);
        config(['services.google.client_secret' => '']);

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }

    public function test_google_callback_creates_and_authenticates_verified_user(): void
    {
        $mockSocialiteUser = Mockery::mock(SocialiteUserContract::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-unique-id-12345');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Budi Mahasiswa');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('budi@gmail.com');
        $mockSocialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($mockSocialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'budi@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('google-unique-id-12345', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_google_callback_links_existing_user_and_verifies_email(): void
    {
        $existingUser = User::factory()->unverified()->create([
            'email' => 'siti@gmail.com',
            'google_id' => null,
        ]);

        $this->assertFalse($existingUser->hasVerifiedEmail());

        $mockSocialiteUser = Mockery::mock(SocialiteUserContract::class);
        $mockSocialiteUser->shouldReceive('getId')->andReturn('google-unique-id-67890');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Siti Aminah');
        $mockSocialiteUser->shouldReceive('getEmail')->andReturn('siti@gmail.com');
        $mockSocialiteUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($mockSocialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existingUser);
        $response->assertRedirect('/dashboard');

        $existingUser->refresh();
        $this->assertSame('google-unique-id-67890', $existingUser->google_id);
        $this->assertTrue($existingUser->hasVerifiedEmail());
    }
}
