<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (! $this->app->environment('local') || request()->header('x-forwarded-proto') === 'https' || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Kustomisasi Email Reset Password ke Bahasa Indonesia
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Atur Ulang Kata Sandi - Academic Hub')
                ->greeting('Halo, ' . ($notifiable->name ?? 'Mahasiswa') . '!')
                ->line('Kami menerima permintaan pengaturan ulang kata sandi untuk akun Academic Hub Anda.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line('Tautan atur ulang kata sandi ini akan kedaluwarsa dalam 60 menit.')
                ->line('Jika Anda tidak merasa mengajukan permintaan ini, silakan abaikan email ini dan akun Anda akan tetap aman.')
                ->salutation("Salam hangat,\nTim Academic Hub");
        });
    }
}
