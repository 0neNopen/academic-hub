<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_password_link_sends_to_telegram_when_user_has_chat_id(): void
    {
        Notification::fake();
        $this->mock(\App\Services\TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessageWithId')->once()->andReturn(9999);
        });

        $user = User::factory()->create(['telegram_chat_id' => '123456789']);

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status');
    }

    public function test_password_reset_edits_telegram_message_and_sends_confirmation(): void
    {
        Notification::fake();
        $this->mock(\App\Services\TelegramService::class, function ($mock) {
            $mock->shouldReceive('sendMessageWithId')->once()->andReturn(5555);
            $mock->shouldReceive('editMessageText')->once()->with(123456789, 5555, \Mockery::any(), \Mockery::any())->andReturn(true);
            $mock->shouldReceive('sendMessage')->once()->with(123456789, \Mockery::any(), \Mockery::any())->andReturn(true);
        });

        $user = User::factory()->create(['telegram_chat_id' => '123456789']);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_password_link_handles_mailer_exception_gracefully(): void
    {
        Notification::fake();
        $user = User::factory()->create(); // No telegram_chat_id

        // Mock notification to throw error
        \Illuminate\Support\Facades\Notification::shouldReceive('send')
            ->andThrow(new \Exception('Connection to mail host timed out'));

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHasErrors(['email']);
    }
}


